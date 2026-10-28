<?php

namespace App\Http\Controllers;

use App\Exceptions\AdminException;
use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuditLogController extends Controller
{
    public function __construct(private PermissionService $p) {}

    private function query(Request $r, array $f)
    {
        $q = AuditLog::query();
        $u = $r->user();
        $this->p->require($u, 'audit.view');
        if (! $this->p->allScope($u)) {
            $ids = $this->p->scope(School::query(), $u)->pluck('id')->all();
            $hazards = Hazard::whereRaw('EXISTS(SELECT 1 FROM schools WHERE ST_Intersects(schools.catchment,hazards.location) AND schools.id IN ('.implode(',', array_map('intval', $ids ?: [0])).'))')->select('id');
            $q->where(fn ($a) => $a->whereIn('school_id', $ids)->orWhere(fn ($x) => $x->whereNull('school_id')->where(fn ($b) => $b->whereIn('meta_json->school_id', $ids)->orWhere(fn ($c) => $c->where('entity', 'school')->whereIn('entity_id', $ids))->orWhere(fn ($c) => $c->where('entity', 'hazard')->whereIn('entity_id', $hazards)))));
        }
        if ($f['school_id'] ?? null) {
            if (! $this->p->inScope($u, (int) $f['school_id'])) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Filter sekolah di luar cakupan.');
            }$q->where(fn ($a) => $a->where('school_id', $f['school_id'])->orWhere('meta_json->school_id', (int) $f['school_id']));
        }
        foreach (['action' => 'action', 'actor' => 'actor_id', 'category' => 'category', 'role' => 'actor_role', 'result' => 'result'] as $field => $col) {
            if ($f[$field] ?? null) {
                $q->where($col, $f[$field]);
            }
        }
        if ($r->boolean('critical')) {
            $q->where('severity', 'critical');
        }
        $q->whereRaw('COALESCE(occurred_at,created_at) >= ?', [$f['from'].' 00:00:00'])->whereRaw('COALESCE(occurred_at,created_at) <= ?', [$f['until'].' 23:59:59.999999']);
        if ($f['q'] ?? null) {
            $term = '%'.mb_strtolower($f['q']).'%';
            $q->where(function ($b) use ($term) {
                foreach (['action', 'actor_name', 'actor_email', 'target_label', 'reason'] as $col) {
                    $b->orWhereRaw('LOWER('.$col.') LIKE ?', [$term]);
                }
            });
        }

        return $q;
    }

    public function index(Request $r)
    {
        $f = $r->validate(['q' => 'nullable|string|max:100', 'from' => 'nullable|date_format:Y-m-d', 'until' => 'nullable|date_format:Y-m-d', 'actor' => 'nullable|integer|min:1', 'action' => 'nullable|string|max:100', 'category' => ['nullable', Rule::in(['auth', 'user', 'permission', 'school', 'hazard', 'verification', 'escalation', 'data', 'system'])], 'role' => ['nullable', Rule::in(config('ruteaman_admin.admin_tiers'))], 'school_id' => 'nullable|integer|exists:schools,id', 'result' => 'nullable|in:success,denied,failed', 'critical' => 'nullable|boolean', 'cursor' => 'nullable|string|max:4000', 'csv' => 'nullable|boolean']);
        $f['from'] ??= now()->subDays(6)->toDateString();
        $f['until'] ??= now()->toDateString();
        if ($f['until'] < $f['from']) {
            throw ValidationException::withMessages(['until' => 'Tanggal akhir harus setelah tanggal awal.']);
        }
        $q = $this->query($r, $f);
        $identity = $f;
        unset($identity['cursor'],$identity['csv']);
        ksort($identity);
        $filterHash = hash('sha256', json_encode($identity));
        if ($r->boolean('csv')) {
            $this->p->require($r->user(), 'exports.run', $r->user()->school);
            if (Carbon::parse($f['from'])->diffInDays(Carbon::parse($f['until'])) >= config('ruteaman_audit.csv_max_days')) {
                throw ValidationException::withMessages(['from' => 'Ekspor maksimal 31 hari.']);
            }
            if ((clone $q)->limit(config('ruteaman_audit.csv_max_rows') + 1)->get(['id'])->count() > config('ruteaman_audit.csv_max_rows')) {
                throw ValidationException::withMessages(['from' => 'Terlalu banyak entri. Persempit rentang atau filter.']);
            }
            $ceiling = (clone $q)->max('id') ?? 0;
            $q->where('id', '<=', $ceiling);
            app(AuditLogger::class)->record($r->user(), 'data.audit_exported', 'audit', null, ['from' => $f['from'], 'until' => $f['until']], $r->user()->school_id);

            return response()->streamDownload(function () use ($q) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Waktu UTC', 'Pelaku', 'Alias', 'Tingkat', 'Aksi', 'Target', 'Hasil', 'Penting', 'IP', 'User agent'], ';', escape: '');
                foreach ($q->orderByRaw('COALESCE(occurred_at,created_at) DESC')->orderByDesc('id')->cursor() as $l) {
                    $cells = [$l->id, ($l->occurred_at ?? $l->created_at)?->toIso8601String(), $l->actor_name, $l->actor_email, $l->actor_role, $l->action, $l->target_label, $l->result, $l->severity, $l->ip_address, $l->user_agent];
                    fputcsv($out, array_map([self::class, 'csvCell'], $cells), ';', escape: '');
                }fclose($out);
            }, 'ruteaman-audit.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        }
        $ceiling = (clone $q)->max('id') ?? 0;
        if ($f['cursor'] ?? null) {
            try {
                $c = json_decode(Crypt::decryptString($f['cursor']), true, 512, JSON_THROW_ON_ERROR);
                if (($c['filter'] ?? '') !== $filterHash) {
                    throw new \RuntimeException;
                }$ceiling = (int) $c['ceiling'];
                $q->where(fn ($x) => $x->whereRaw('COALESCE(occurred_at,created_at) < ?', [$c['at']])->orWhere(fn ($y) => $y->whereRaw('COALESCE(occurred_at,created_at) = ?', [$c['at']])->where('id', '<', (int) $c['id'])));
            } catch (\Throwable) {
                throw ValidationException::withMessages(['cursor' => 'Halaman audit kedaluwarsa/tidak sesuai filter. Mulai kembali dari halaman pertama.']);
            }
        }
        $rows = $q->where('id', '<=', $ceiling)->orderByRaw('COALESCE(occurred_at,created_at) DESC')->orderByDesc('id')->limit(31)->get();
        $more = $rows->count() > 30;
        $logs = $rows->take(30);
        $last = $logs->last();
        $next = $more && $last ? Crypt::encryptString(json_encode(['at' => ($last->occurred_at ?? $last->created_at)->format('Y-m-d H:i:s.u'), 'id' => $last->id, 'ceiling' => $ceiling, 'filter' => $filterHash])) : null;
        $schools = $this->p->scope(School::query(), $r->user())->orderBy('name')->get();

        return response()->view('admin.audit', ['logs' => $logs, 'nextCursor' => $next, 'filters' => $f, 'schools' => $schools, 'actors' => User::whereIn('id', $logs->pluck('actor_id')->filter())->get()->keyBy('id')])->header('Cache-Control', 'private, no-store');
    }

    public static function csvCell(mixed $v): string
    {
        $v = (string) ($v ?? '');

        return preg_match('/^[\s\x00-\x1f]*[=+\-@]/u', $v) ? "'".$v : $v;
    }
}
