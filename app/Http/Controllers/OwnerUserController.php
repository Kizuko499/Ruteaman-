<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\OwnerUserRequest;
use App\Models\School;
use App\Models\User;
use App\Services\AdminAccessService;
use App\Services\AdminAccountService;
use App\Services\AdminPasswordPolicy;
use App\Services\OwnerUserService;
use App\Services\QuickUserDeletion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OwnerUserController extends Controller
{
    public function __construct(private OwnerUserService $service) {}

    public function index(Request $r)
    {
        $this->service->owner($r->user());
        $d = $r->validate(['q' => 'nullable|string|max:100', 'role' => ['nullable', Rule::in([...config('ruteaman_admin.admin_tiers'), 'student', 'verifier'])], 'school_id' => 'nullable|integer|exists:schools,id', 'status' => 'nullable|in:active,pending,suspended']);
        $q = User::with('school');
        if ($d['q'] ?? null) {
            $q->where(fn ($x) => $x->where('display_name', 'like', '%'.$d['q'].'%')->orWhere('email', 'like', '%'.$d['q'].'%'));
        }foreach (['role', 'school_id', 'status'] as $k) {
            if ($d[$k] ?? null) {
                $q->where($k, $d[$k]);
            }
        }

        return view('admin.users.index', ['users' => $q->orderBy('display_name')->orderBy('id')->paginate(20)->withQueryString(), 'schools' => School::orderBy('name')->get()]);
    }

    public function create(Request $r)
    {
        $this->service->owner($r->user());

        return view('admin.users.form', ['target' => null, 'schools' => School::orderBy('name')->get()]);
    }

    public function show(Request $r, User $user)
    {
        $this->service->owner($r->user());

        return response()->view('admin.users.form', ['target' => $user, 'schools' => School::orderBy('name')->get(), 'canPermanent' => $user->status === 'pending' && ! $this->service->related($user), 'deleteToken' => $user->id !== $r->user()->id ? app(QuickUserDeletion::class)->issue($r, $user) : null])->header('Cache-Control', 'private, no-store');
    }

    private function done(Request $r, string $text)
    {
        return $r->expectsJson() ? response()->json(['message' => $text]) : back()->with('success', $text);
    }

    private function once(Request $r, array $d, User $u)
    {
        return $r->expectsJson() ? response()->json(['message' => 'Tautan sekali tampil.', 'invite' => ['url' => $d['url'], 'mail_sent' => $d['mail_sent']]])->header('Cache-Control', 'private, no-store') : response()->view('admin.invite-once', [...$d, 'target' => $u])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function store(OwnerUserRequest $r, AdminAccountService $accounts)
    {
        $u = $accounts->create($r->user(), $r->validated());
        if ($r->validated('creation_method') === 'invite') {
            return $this->once($r, $accounts->issue($r->user(), $u, false, true), $u);
        }

        return $r->expectsJson() ? response()->json(['message' => 'Akun dibuat.', 'id' => $u->id]) : to_route('admin.users.show', $u)->with('success', 'Akun dibuat; kata sandi tidak ditampilkan lagi.');
    }

    public function update(OwnerUserRequest $r, User $user)
    {
        $this->service->profile($r->user(), $user, $r->validated());

        return $this->done($r, 'Profil diperbarui dan sesi lama dicabut.');
    }

    public function permissions(OwnerUserRequest $r, User $user, AdminAccessService $a)
    {
        $this->service->owner($r->user());
        $a->updatePermissions($r->user(), $user, $r->validated('permissions', []));

        return $this->done($r, 'Izin diperbarui dan dicatat.');
    }

    public function role(OwnerUserRequest $r, User $user, AdminAccessService $a)
    {
        $this->service->owner($r->user());
        $a->tier($r->user(), $user, Role::from($r->validated('role')), $r->validated('school_id'), []);

        return $this->done($r, 'Peran diperbarui; sesi lama dicabut.');
    }

    public function password(OwnerUserRequest $r, User $user)
    {
        $this->service->password($r->user(), $user, $r->validated());

        return $this->done($r, 'Kata sandi diatur tanpa ditampilkan atau dikirim lewat email.');
    }

    public function generated(Request $r, AdminPasswordPolicy $p)
    {
        $this->service->owner($r->user());

        return response()->json(['password' => $p->generate()])->header('Cache-Control', 'private, no-store')->header('Pragma', 'no-cache');
    }

    public function reset(Request $r, User $user, AdminAccountService $a)
    {
        $this->service->owner($r->user());

        return $this->once($r, $a->issue($r->user(), $user, true), $user);
    }

    public function lifecycle(Request $r, User $user, AdminAccessService $a)
    {
        $this->service->owner($r->user());
        $a->lifecycle($r->user(), $user, $r->route('action'));

        return $this->done($r, 'Status/sesi akun diperbarui.');
    }

    public function quickDestroy(Request $r, User $user)
    {
        $this->service->owner($r->user());
        $data = $r->validate(['delete_token' => 'required|string|max:200', 'confirmed' => 'required|accepted']);
        $this->service->quickDelete($r, $user, $data['delete_token']);

        return $r->expectsJson() ? response()->json(['message' => 'Akun dihapus dan histori dipertahankan.']) : to_route('admin.users.index')->with('success', 'Akun dihapus; laporan dan histori tetap dipertahankan.');
    }

    public function destroy(OwnerUserRequest $r, User $user)
    {
        $this->service->delete($r->user(), $user, $r->validated());

        return $r->expectsJson() ? response()->json(['message' => 'Penghapusan dicatat.']) : to_route('admin.users.index')->with('success', 'Penghapusan dicatat; histori tetap dipertahankan.');
    }
}
