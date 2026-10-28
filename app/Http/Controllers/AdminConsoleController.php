<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exceptions\AdminException;
use App\Http\Requests\AdminMutationRequest;
use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\School;
use App\Models\User;
use App\Models\Verification;
use App\Policies\AdminPolicy;
use App\Services\AdminAccessService;
use App\Services\AdminAccountService;
use App\Services\AdminAuditService;
use App\Services\AvatarService;
use App\Services\GlobalFeatureSettings;
use App\Services\PermissionService;
use App\Services\SchoolProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminConsoleController extends Controller
{
    public function __construct(private PermissionService $p, private AdminAccountService $accounts, private AdminAccessService $access, private SchoolProvisioningService $schools) {}

    private function schoolsQuery(Request $r)
    {
        return $this->p->scope(School::query(), $r->user());
    }

    private function adminsQuery(Request $r)
    {
        $q = User::whereIn('role', ['owner', 'platform_admin', 'school_admin', 'school_staff']);
        if (! $this->p->allScope($r->user())) {
            $ids = $this->schoolsQuery($r)->pluck('id');
            $q->whereIn('school_id', $ids);
        }if ($r->user()->role !== Role::Owner) {
            $q->whereIn('role', array_map(fn ($x) => $x->value, array_filter(Role::cases(), fn ($x) => $x->admin() && $x->tier() > $r->user()->role->tier())));
        }

        return $q;
    }

    private function auditQuery(Request $r)
    {
        $q = AuditLog::query();
        if (! $this->p->allScope($r->user())) {
            $ids = $this->schoolsQuery($r)->pluck('id')->all();
            $hazards = Hazard::whereRaw('EXISTS(SELECT 1 FROM schools WHERE ST_Intersects(schools.catchment,hazards.location) AND schools.id IN ('.implode(',', array_map('intval', $ids ?: [0])).'))')->select('id');
            $q->where(function ($q) use ($ids, $hazards) {
                $q->whereIn('meta_json->school_id', $ids)->orWhere(fn ($a) => $a->where('entity', 'school')->whereIn('entity_id', $ids))->orWhere(fn ($a) => $a->where('entity', 'hazard')->whereIn('entity_id', $hazards));
            });
        }

        return $q;
    }

    private function filters(Request $r): array
    {
        return $r->validate(['q' => 'nullable|string|max:100', 'role' => ['nullable', Rule::in(config('ruteaman_admin.admin_tiers'))], 'school_id' => 'nullable|integer', 'status' => 'nullable|in:active,pending,suspended,paused,archived', 'action' => 'nullable|string|max:100', 'actor' => 'nullable|integer', 'from' => 'nullable|date', 'until' => 'nullable|date']);
    }

    public function index(Request $r)
    {
        $schools = $this->schoolsQuery($r);
        $admins = $this->adminsQuery($r);
        $stats = [
            'Institusi aktif' => (clone $schools)->where('status', 'active')->count(),
            'Total sekolah' => (clone $schools)->where('institution_type', 'school')->count(),
            'Total kampus' => (clone $schools)->where('institution_type', 'campus')->count(),
            'Usulan baru' => \App\Models\InstitutionSuggestion::where('status', 'new')->count(),
            'Sekolah tanpa NPSN' => (clone $schools)->where('institution_type', 'school')->where(function($w){ $w->whereNull('npsn')->orWhere('npsn',''); })->count(),
            'Admin aktif' => (clone $admins)->where('status', 'active')->count(),
            'Undangan menunggu' => (clone $admins)->where('status', 'pending')->count(),
            'Sekolah dijeda' => (clone $schools)->where('status', 'paused')->count(),
        ];
        $logs = $this->p->can($r->user(), 'audit.view') ? $this->auditQuery($r)->latest('id')->limit(10)->get() : collect();
        $recentSuggestions = \App\Models\InstitutionSuggestion::orderByDesc('id')->limit(5)->get(['id', 'ticket_number', 'proposed_name', 'status', 'created_at']);

        return view('admin.overview', compact('stats', 'logs', 'recentSuggestions'));
    }

    public function admins(Request $r)
    {
        $f = $this->filters($r);
        $q = $this->adminsQuery($r)->with('school');
        if ($f['q'] ?? null) {
            $q->where(fn ($q) => $q->where('display_name', 'ilike', '%'.$f['q'].'%')->orWhere('email', 'ilike', '%'.$f['q'].'%'));
        }foreach (['role', 'school_id', 'status'] as $k) {
            if ($f[$k] ?? null) {
                $q->where($k, $f[$k]);
            }
        }

        return view('admin.admins', ['admins' => $q->orderBy('display_name')->paginate(20)->withQueryString(), 'schools' => $this->schoolsQuery($r)->orderBy('name')->get()]);
    }

    public function createAdmin(Request $r)
    {
        $this->p->require($r->user(), 'users.manage_admins', $r->user()->school, true);

        return view('admin.admin-form', ['target' => null, 'schools' => $this->schoolsQuery($r)->orderBy('name')->get()]);
    }

    public function showAdmin(Request $r, User $user)
    {
        abort_unless((new AdminPolicy)->viewUser($r->user(), $user), 403);

        return view('admin.admin-form', ['target' => $user, 'schools' => $this->schoolsQuery($r)->orderBy('name')->get()]);
    }

    public function storeAdmin(AdminMutationRequest $r)
    {
        $u = $this->accounts->create($r->user(), $r->validated());
        if (($r->validated('creation_method') ?? 'invite') === 'password_set') {
            return $this->done($r, 'Akun dibuat dengan kata sandi awal. Kata sandi tidak ditampilkan atau dikirim lewat email.');
        }$invite = $this->accounts->issue($r->user(), $u, false, true);

        return $this->once($r, $invite, $u);
    }

    public function updateAdmin(AdminMutationRequest $r, User $user)
    {
        $this->access->updatePermissions($r->user(), $user, $r->validated('permissions', []), $r->validated('school_scope'));

        return $this->done($r, 'Izin dan cakupan diperbarui.');
    }

    public function tier(AdminMutationRequest $r, User $user)
    {
        $this->access->tier($r->user(), $user, Role::from($r->validated('role')), $r->validated('school_id'), $r->validated('school_scope', []));

        return $this->done($r, 'Tingkat diperbarui; izin di luar langit-langit dicabut.');
    }

    public function lifecycle(Request $r, User $user)
    {
        $action = $r->route('action');
        $avatar = null;
        if ($action === 'delete') {
            DB::transaction(function () use ($r, $user) {
                $this->access->lockOwners();
                $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $this->access->lastOwner($target);
                $this->access->manageable($r->user(), $target);
            });
            $d = $r->validate(['confirmation' => 'required|string|max:254', 'delete_password' => 'required|string']);
            if (! Hash::check($d['delete_password'], $r->user()->password)) {
                throw ValidationException::withMessages(['delete_password' => 'Kata sandi Anda tidak sesuai.']);
            }
            if (! in_array($d['confirmation'], [$user->display_name, $user->email], true)) {
                throw ValidationException::withMessages(['confirmation' => 'Ketik nama atau alias target yang tepat.']);
            }$avatar = $user->avatar_path;
        }
        $this->access->lifecycle($r->user(), $user, $action);
        if ($avatar) {
            app(AvatarService::class)->removeFiles($avatar);
        }

        return $this->done($r, 'Perubahan akun dicatat.');
    }

    public function issue(Request $r, User $user)
    {
        $result = $this->accounts->issue($r->user(), $user, $r->routeIs('admin.admins.reset'));

        return $this->once($r, $result, $user);
    }

    private function once(Request $r, array $result, User $u)
    {
        $data = ['url' => $result['url'], 'mail_sent' => $result['mail_sent'], 'target' => $u];

        return $r->expectsJson() ? response()->json(['message' => 'Tautan hanya ditampilkan sekali.', 'invite' => $data])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer') : response()->view('admin.invite-once', $data)->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    private function done(Request $r, string $message)
    {
        return $r->expectsJson() ? response()->json(['message' => $message]) : back()->with('success', $message);
    }

    public function schools(Request $r)
    {
        $f = $this->filters($r);
        $q = $this->schoolsQuery($r);
        if ($f['q'] ?? null) {
            $qq = '%'.mb_strtolower($f['q']).'%';
            $q->where(function($w) use ($qq) {
                $w->whereRaw('LOWER(name) LIKE ?', [$qq])
                  ->orWhereRaw('LOWER(npsn) LIKE ?', [$qq]);
            });
        }if ($f['status'] ?? null) {
            $q->where('status', $f['status']);
        }
        if ($r->get('type')) {
            $q->where('institution_type', $r->get('type'));
        }
        $sort = $r->get('sort', 'name');
        if ($sort === 'newest') $q->orderByDesc('id');
        elseif ($sort === 'oldest') $q->orderBy('id');
        else $q->orderBy('name');

        // Filter data bermasalah (perlu join spatial check via having)
        $issue = $r->get('issue');
        if ($issue === 'no_npsn') {
            $q->where(function($w){ $w->whereNull('npsn')->orWhere('npsn',''); });
        } elseif ($issue === 'no_coords') {
            $q->whereNull('location');
        }

        $schools = $q->paginate(20)->withQueryString();
        // Tambah koordinat untuk setiap sekolah (satu query)
        $ids = $schools->pluck('id')->all();
        $coords = [];
        if ($ids) {
            $rows = \Illuminate\Support\Facades\DB::select(
                "SELECT id, ST_Y(location) as lat, ST_X(location) as lng FROM schools WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")"
            );
            foreach ($rows as $row) $coords[$row->id] = $row;
        }
        return view('admin.schools', compact('schools', 'coords'));
    }

    /**
     * Pemindai duplikat institusi: kelompok NPSN sama dan nama+koordinat sama.
     * Sifatnya hanya meninjau — tidak ada penggabungan/hapus otomatis; admin
     * mengarsipkan salinan berlebih langsung dari halaman ini.
     */
    public function schoolSearch(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
        $rows = $this->schoolsQuery($r)
            ->where('status', '!=', 'archived')
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'status']);

        return response()->json($rows->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'status' => $s->status])->values());
    }

    public function schoolDuplicates(Request $r)
    {
        $this->p->require($r->user(), 'schools.view');

        $rows = $this->schoolsQuery($r)
            ->select(['id', 'name', 'npsn', 'status', 'institution_type', 'public_slug'])
            ->selectRaw('ST_Y(location) as lat, ST_X(location) as lng')
            ->get();
        $finderRows = $rows->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->name, 'npsn' => $s->npsn, 'lat' => $s->lat !== null ? (float) $s->lat : null, 'lng' => $s->lng !== null ? (float) $s->lng : null])->all();
        $groups = \App\Support\InstitutionDuplicateFinder::groups($finderRows);
        $byId = $rows->keyBy('id');
        $memberIds = collect($groups)->flatMap(fn ($g) => $g['member_ids'])->unique()->values()->all();
        $accountCounts = $memberIds ? User::whereIn('school_id', $memberIds)->selectRaw('school_id, COUNT(*) as c')->groupBy('school_id')->pluck('c', 'school_id') : collect();
        $reportCounts = $memberIds ? \Illuminate\Support\Facades\DB::table('hazard_reports')->whereIn('school_id', $memberIds)->selectRaw('school_id, COUNT(*) as c')->groupBy('school_id')->pluck('c', 'school_id') : collect();

        foreach ($groups as &$g) {
            $g['members'] = [];
            $bestId = null;
            $bestScore = -1;
            foreach ($g['member_ids'] as $id) {
                $school = $byId[$id];
                $accounts = (int) ($accountCounts[$id] ?? 0);
                $reports = (int) ($reportCounts[$id] ?? 0);
                $score = ($school->status === 'active' ? 3 : 0) + ($accounts > 0 ? 3 : 0) + min($reports, 5) + (filled($school->npsn) ? 2 : 0);
                $g['members'][] = ['school' => $school, 'accounts' => $accounts, 'reports' => $reports, 'lat' => $school->lat, 'lng' => $school->lng];
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestId = $id;
                }
            }
            $g['keep_id'] = $bestId;
        }
        unset($g);

        $perPage = 20;
        $page = max(1, (int) $r->query('page', 1));
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($groups, ($page - 1) * $perPage, $perPage),
            count($groups),
            $perPage,
            $page,
            ['path' => $r->url()]
        );

        return view('admin.schools-duplicates', [
            'groups' => $paginator,
            'totalGroups' => count($groups),
            'totalInstitutions' => $rows->count(),
            'canManage' => $r->user()->role->tier() <= 2,
        ]);
    }

    public function createSchool(Request $r)
    {
        $this->p->require($r->user(), 'schools.provision');

        $catalog=null;$d=$r->validate(['catalog_id'=>'nullable|integer|exists:campus_catalog,id']);
        if(!empty($d['catalog_id'])){$catalog=\App\Models\CampusCatalogEntry::findOrFail($d['catalog_id']);if($catalog->school_id)throw \Illuminate\Validation\ValidationException::withMessages(['catalog_id'=>'Entri ini sudah terhubung ke institusi. Buka institusi yang terdaftar.']);}
        return view('admin.school-form', ['school' => null,'catalog'=>$catalog]);
    }

    public function showSchool(Request $r, School $school)
    {
        $this->p->require($r->user(), 'schools.view', $school);
        $geometry = DB::selectOne('SELECT '.\App\Services\SpatialSql::coordinates('lat','lng').',ST_AsGeoJSON(catchment) AS polygon FROM schools WHERE id=?', [$school->id]);
        $changeLogs = \App\Models\InstitutionChangeLog::with('changer')->where('institution_id', $school->id)->orderByDesc('id')->limit(20)->get();
        $accounts = User::where('school_id', $school->id)->whereIn('role', [Role::SchoolAdmin, Role::SchoolStaff])->orderBy('display_name')->get();
        $deleteBlockers = ['accounts' => User::where('school_id', $school->id)->count(), 'reports' => DB::table('hazard_reports')->where('school_id', $school->id)->count()];

        return view('admin.school-form', ['school' => $school, 'geometry' => $geometry, 'changeLogs' => $changeLogs, 'accounts' => $accounts, 'deleteBlockers' => $deleteBlockers, 'generalFeatures' => app(GlobalFeatureSettings::class)->defaults()]);
    }

    public function storeSchool(AdminMutationRequest $r)
    {
        if (! in_array($r->validated('role'), ['school_admin'], true)) {
            throw new AdminException('VALIDATION_FAILED', 'Akun pengelola institusi harus memakai tingkat Admin Sekolah / Kampus.', 422);
        }$result = $this->schools->create($r->user(), $r->validated());
        if (($r->validated('creation_method') ?? 'invite') === 'password_set') {
            return $this->done($r, 'Sekolah / kampus dan akun aktif dibuat dengan kata sandi awal.');
        }

        return $this->once($r, $result, $result['user']);
    }

    /** Buat akun pengelola pertama/ tambahan untuk institusi yang sudah ada (dari halaman detail). */
    public function storeSchoolAccount(Request $r, School $school)
    {
        $data = $r->validate([
            'display_name' => ['required', 'string', 'min:2', 'max:100', \Illuminate\Validation\Rule::notIn(config('ruteaman_admin.reserved'))],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'contact_email' => ['required', 'email', 'max:254'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);
        $result = $this->schools->createAccount($r->user(), $school, $data);

        return $this->once($r, $result, $result['user']);
    }

    public function updateSchool(AdminMutationRequest $r, School $school)
    {
        $this->schools->update($r->user(), $school, $r->validated());

        return $this->done($r, 'Data institusi diperbarui.');
    }

    public function schoolStatus(Request $r, School $school)
    {
        $this->schools->status($r->user(), $school, $r->route('school_status'));

        return $this->done($r, 'Status institusi diperbarui.');
    }

    public function schoolDelete(Request $r, School $school)
    {
        try {
            $this->schools->delete($r->user(), $school);
        } catch (\App\Exceptions\AdminException $e) {
            return back()->withErrors(['school_delete' => $e->getMessage()]);
        }

        return $this->done($r, 'Institusi terhapus permanen.');
    }

    public function features(Request $r)
    {
        $this->p->require($r->user(), 'schools.features');

        $global = app(GlobalFeatureSettings::class);

        $q = trim((string) $r->query('q', ''));
        $schools = $this->schoolsQuery($r)->orderBy('name');
        if ($q !== '') {
            $schools->where('name', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%');
        }

        return view('admin.features', ['schools' => $schools->paginate(10)->appends($q !== '' ? ['q' => $q] : [])->fragment('khusus'), 'generalFeatures' => $global->defaults(), 'generalUpdatedAt' => $global->updatedAt(), 'q' => $q]);
    }

    public function updateFeatures(AdminMutationRequest $r, School $school)
    {
        $this->schools->features($r->user(), $school, array_map(fn ($v) => (bool) $v, $r->validated('features')));

        return $this->done($r, 'Saklar fitur diperbarui.');
    }

    public function allFeatures(AdminMutationRequest $r)
    {
        $this->schools->all($r->user(), array_map(fn ($v) => (bool) $v, $r->validated('features')));

        return $this->done($r, 'Fitur diterapkan ke seluruh sekolah.');
    }

    public function generalFeatures(AdminMutationRequest $r)
    {
        app(GlobalFeatureSettings::class)->apply($r->user(), array_map(fn ($v) => (bool) $v, $r->validated('features')));

        return $this->done($r, 'Pengaturan umum disimpan, diterapkan ke semua sekolah terdaftar, dan menjadi default sekolah baru.');
    }

    public function roles()
    {
        return view('admin.roles');
    }

    public function reauthForm()
    {
        return view('admin.reauth');
    }

    public function reauth(Request $r)
    {
        $d = $r->validate(['password' => 'required|string']);
        if (! Hash::check($d['password'], $r->user()->password)) {
            app(AdminAuditService::class)->write($r->user(), 'reauth.failed', 'user', $r->user()->id);
            throw ValidationException::withMessages(['password' => 'Kata sandi tidak sesuai.']);
        }$r->session()->put('admin.reauth_at', time());

        return $r->expectsJson() ? response()->json(['message' => 'Konfirmasi berlaku 10 menit.']) : to_route('admin.index')->with('success', 'Kata sandi dikonfirmasi selama 10 menit.');
    }

    public function hazards(Request $r)
    {
        $s = $this->p->selectedSchool($r, 'hazards.view');
        if (! $s) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Pilih sekolah dalam cakupan.');
        }$this->p->require($r->user(), 'hazards.view', $s);

        return view('admin.hazards', ['school' => $s, 'schools' => $this->schoolsQuery($r)->orderBy('name')->get(), 'hazards' => Hazard::inSchool($s->id)->with('type')->orderByDesc('id')->paginate(20)]);
    }

    public function reopen(Request $r, Hazard $hazard)
    {
        DB::transaction(function () use ($r, $hazard) {
            $h = Hazard::whereKey($hazard->id)->lockForUpdate()->firstOrFail();
            abort_unless($h->publication_control === 'normal', 409, 'Pulihkan penangguhan terlebih dahulu. Laporan yang dihapus tidak dapat dibuka kembali.');
            if (! in_array($h->status->value, ['resolved', 'expired'], true)) {
                throw new AdminException('VALIDATION_FAILED', 'Hanya kondisi selesai atau kedaluwarsa yang dapat dibuka kembali.', 422);
            }Verification::create(['hazard_id' => $h->id, 'verifier_id' => $r->user()->id, 'decision' => 'needs_evidence', 'note' => 'Dibuka kembali; menunggu bukti baru.', 'created_at' => now()]);
            $h->update(['status' => 'disputed', 'resolved_at' => null, 'last_confirmed_at' => now()]);
            app(AdminAuditService::class)->write($r->user(), 'hazard.reopened', 'hazard', $h->id);
        });

        return $this->done($r, 'Hazard dibuka kembali dan memerlukan bukti baru.');
    }
}
