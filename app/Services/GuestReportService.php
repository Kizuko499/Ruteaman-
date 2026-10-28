<?php

namespace App\Services;

use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class GuestReportService
{
    public function __construct(private PermissionService $permissions, private HazardLookupService $lookup, private PhotoSanitizer $photos, private HazardEvidenceService $evidence) {}

    public function submit(array $d, ?UploadedFile $photo): array
    {
        $path = null;
        try {
            return DB::transaction(function () use ($d, $photo, &$path) {
                $school = School::whereKey($d['school_id'])->lockForUpdate()->firstOrFail();
                $this->permissions->requireFeature($school, 'reporting', true);
                $lat = (float) $d['latitude'];
                $lng = (float) $d['longitude'];
                // Jangkauan laporan tidak dibatasi wilayah: titik mana pun boleh
                // dilaporkan untuk sekolah/kampus yang dipilih.
                // The same category lock as authenticated reports prevents concurrent duplicate creation.
                $type = HazardType::whereKey($d['type_id'])->lockForUpdate()->firstOrFail();
                if ($this->lookup->nearby($type->id, $lat, $lng, $school->id)) {
                    return ['duplicate' => true, 'school' => $school->name];
                }
                $id = DB::table('hazards')->insertGetId([
                    'type_id' => $type->id, 'location' => SpatialSql::point($lng, $lat), 'severity' => $type->severity_weight,
                    'status' => 'new', 'requires_moderation' => app(ReportPublicationPolicy::class)->requiresReview($school, true), 'published_at' => app(ReportPublicationPolicy::class)->requiresReview($school, true) ? null : now(), 'confidence' => 0, 'is_demo' => false,
                    'first_reported_at' => now(), 'public_slug' => (string) Str::ulid(),
                    'location_label' => trim($d['location_label'] ?? '') ?: null, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $h = Hazard::findOrFail($id);
                $path = $photo ? $this->photos->store($photo) : null;
                $h->reports()->create(['reporter_id' => null, 'is_guest' => true, 'school_id' => $school->id,
                    'guest_name' => trim($d['guest_name'] ?? '') ?: null, 'description' => $d['description'] ?? null, 'photo_path' => $path, 'observed_at' => now()]);
                $this->evidence->refresh($h);
                $this->evidence->audit(null, 'report.created', $id, ['guest' => true, 'school_id' => $school->id]);

                DB::afterCommit(fn () => app(\App\Services\ReportMailNotifier::class)->submitted($h));

                return ['duplicate' => false, 'school' => $school->name, 'public' => ! $h->requires_moderation];
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('hazard_photos')->delete($path);
            }throw $e;
        }
    }
}
