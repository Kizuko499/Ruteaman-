<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\School;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSchoolDataController extends Controller
{
    public function export(Request $r, School $school, PermissionService $p)
    {
        $p->require($r->user(), 'exports.run', $school);
        app(AdminAuditService::class)->write($r->user(), 'data.exported', 'school', $school->id, [], $school->id);

        return response()->streamDownload(function () use ($school) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Slug', 'Jenis', 'Status', 'Laporan', 'Konfirmasi', 'Terakhir dikonfirmasi'], ';', escape: '');
            foreach (Hazard::inSchool($school->id)->with('type')->withCount(['reports', 'confirmations'])->orderBy('id')->cursor() as $h) {
                fputcsv($f, [$h->public_slug, $h->type->code, $h->status->value, $h->reports_count, $h->confirmations_count, $h->last_confirmed_at?->toIso8601String()], ';', escape: '');
            }fclose($f);
        }, 'ruteaman-school-'.$school->id.'.csv', ['Content-Type' => 'text/csv', 'Cache-Control' => 'private, no-store']);
    }

    public function settings(Request $r, School $school, PermissionService $p)
    {
        $p->require($r->user(), 'feed.manage', $school);

        return view('admin.feed-settings', compact('school'));
    }

    public function update(Request $r, School $school, PermissionService $p)
    {
        $d = $r->validate(['public_escalation_status' => 'required|boolean']);
        DB::transaction(function () use ($r, $school, $d, $p) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $s = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            $p->require($u, 'feed.manage', $s, true);
            $s->forceFill(['settings' => array_replace($s->settings ?? [], ['public_escalation_status' => (bool) $d['public_escalation_status']])])->save();
            app(AdminAuditService::class)->write($u, 'school.feed_settings_changed', 'school', $s->id, [], $s->id);
        });

        return back()->with('success', 'Tampilan publik diperbarui.');
    }

    public function metrics(Request $r, School $school, PermissionService $p)
    {
        $p->require($r->user(), 'metrics.view', $school);
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now($school->timezone)->subDays($i)->startOfDay();
            $days[] = ['date' => $date->toDateString(), 'first_reports' => Hazard::inSchool($school->id)->whereBetween('first_reported_at', [$date->clone()->utc(), $date->clone()->addDay()->utc()])->count()];
        }

return view('admin.metrics',compact('school','days'));
    }
}
