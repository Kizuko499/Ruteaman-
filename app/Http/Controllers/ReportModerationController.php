<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Services\AuditLogger;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportModerationController extends Controller
{
    public function update(Request $r, Hazard $hazard, string $action, PermissionService $p)
    {
        abort_unless($r->user()->role->admin(), 403);
        $school = $p->selectedSchool($r, 'hazards.reopen');
        $p->require($r->user(), 'hazards.reopen', $school, true);
        abort_unless(Hazard::whereKey($hazard->id)->inSchool($school->id)->exists(), 403);
        $d = $r->validate(['reason' => 'required|string|min:3|max:500']);
        DB::transaction(function () use ($r, $hazard, $action, $d, $school, $p) {
            $h = Hazard::whereKey($hazard->id)->lockForUpdate()->firstOrFail();
            $p->require($r->user(), 'hazards.reopen', $school, true);
            abort_unless(Hazard::whereKey($h->id)->inSchool($school->id)->exists(), 403);
            abort_if($h->requires_moderation && ! $h->reports()->where('school_id', $school->id)->exists(), 403, 'Laporan antrean berada di luar sekolah yang dipilih.');
            $before = $h->publication_control;
            if ($action === 'restore') {
                abort_unless($before === 'suspended', 409, 'Hanya laporan ditangguhkan yang dapat dipulihkan.');
            }
            if ($before === 'removed') {
                abort(409, 'Laporan sudah dihapus dari publik.');
            }
            $next = match ($action) {
                'suspend' => 'suspended','remove' => 'removed','restore' => 'normal',default => abort(404)
            };
            $h->forceFill(['publication_control' => $next])->save();
            app(AuditLogger::class)->record($r->user(), 'report.'.$action, 'hazard', $h->id, [], $school->id, ['publication_control' => ['before' => $before, 'after' => $next]], $d['reason']);
        });

        return back()->with('success', 'Moderasi disimpan. Histori laporan dan audit tetap dipertahankan.');
    }
}
