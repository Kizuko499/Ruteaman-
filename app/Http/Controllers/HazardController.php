<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHazardRequest;
use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\School;
use App\Services\HazardEvidenceService;
use App\Services\HazardLookupService;
use App\Services\PermissionService;
use App\Services\PhotoSanitizer;
use App\Services\ReportPublicationPolicy;
use App\Services\SpatialSql;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HazardController extends Controller
{
    public function create(): View
    {
        $school = DB::table('schools')->where('id', request()->user()->school_id)->selectRaw(SpatialSql::coordinates('lat', 'lng'))->first();

        return view('hazards.create', ['types' => HazardType::orderBy('label')->get(), 'school' => $school]);
    }

    public function duplicate(Request $request, HazardLookupService $lookup): JsonResponse
    {
        $d = $request->validate(['type_id' => 'required|exists:hazard_types,id', 'latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180']);
        $lookup->guardPoint($request->user(), (float) $d['latitude'], (float) $d['longitude']);
        $h = $lookup->nearby((int) $d['type_id'], (float) $d['latitude'], (float) $d['longitude']);

        return response()->json(['duplicate' => $h ? ['label' => $h->type->label, 'url' => $h->requires_moderation ? route('reports.mine') : route('hazards.public', $h), 'confirm_url' => $h->requires_moderation ? null : route('hazards.confirm', $h), 'pending_review' => $h->requires_moderation] : null]);
    }

    public function store(StoreHazardRequest $request, PhotoSanitizer $photos, HazardLookupService $lookup, HazardEvidenceService $evidence): RedirectResponse
    {
        $d = $request->validated();
        $user = $request->user();
        $lat = (float) $d['latitude'];
        $lng = (float) $d['longitude'];
        $lookup->guardPoint($user, $lat, $lng);
        [$hazard,$duplicate] = DB::transaction(function () use ($d, $user, $request, $photos, $lookup, $evidence, $lat, $lng) {
            $school = School::whereKey($user->school_id)->lockForUpdate()->firstOrFail();
            app(PermissionService::class)->requireFeature($school, 'reporting', true);
            $lookup->guardPoint($user, $lat, $lng);
            $review = app(ReportPublicationPolicy::class)->requiresReview($school, false);
            // Serialize category creation so concurrent duplicate submissions cannot create two hazards.
            $type = HazardType::whereKey($d['type_id'])->lockForUpdate()->firstOrFail();
            if ($existing = $lookup->nearby($type->id, $lat, $lng)) {
                return [$existing, true];
            }
            $location = SpatialSql::point($lng, $lat);
            $id = DB::table('hazards')->insertGetId(['type_id' => $type->id, 'location' => $location, 'is_demo' => str_starts_with($user->school?->bmkg_area_code ?? '', 'DEMO-'), 'location_label' => isset($d['location_label']) ? trim($d['location_label']) : null, 'severity' => $type->severity_weight, 'status' => 'new', 'requires_moderation' => $review, 'published_at' => $review ? null : now(), 'confidence' => 0, 'first_reported_at' => now(), 'public_slug' => (string) Str::ulid(), 'created_at' => now(), 'updated_at' => now()]);
            $h = Hazard::findOrFail($id);
            $path = $request->hasFile('photo') ? $photos->store($request->file('photo')) : null;
            $h->reports()->create(['reporter_id' => $user->id, 'school_id' => $school->id, 'description' => $d['description'] ?? null, 'photo_path' => $path, 'observed_at' => now()]);
            $evidence->refresh($h);
            $evidence->audit($user->id, 'report.created', $id);

            return [$h, false];
        });
        if (! $duplicate) {
            app(\App\Services\ReportMailNotifier::class)->submitted($hazard);
        }
        if ($hazard->requires_moderation) {
            return to_route('reports.mine')->with('success', $duplicate ? 'Laporan serupa sedang menunggu verifikasi. Laporan baru tidak dibuat.' : 'Laporan tersimpan. Menunggu verifikasi pengelola sebelum tampil publik.');
        }

        return to_route('hazards.public', $hazard)->with($duplicate ? 'duplicate' : 'success', $duplicate ? 'Sudah ada laporan serupa di dekat sini. Masih ada? Gunakan tombol konfirmasi; laporan baru tidak dibuat.' : 'Laporan langsung tampil sebagai belum terverifikasi. Pengguna independen dapat mengonfirmasi.');
    }
}
