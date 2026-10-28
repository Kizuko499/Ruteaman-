<?php

namespace App\Services;

use App\Models\Hazard;
use App\Models\School;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

final class MapConditionService
{
    public function query(School $school, ?array $bbox = null)
    {
        // No active()/TTL, resolved window or age predicate here. Route scoring keeps its own active filter.
        $q = Hazard::where('publication_control', 'normal')->where('requires_moderation', false)->select('hazards.*')->selectRaw(SpatialSql::coordinates('latitude', 'longitude'))
            ->with('type')->withCount(['reports', 'confirmations'])
            ->where(function ($q) {
                $q->publicListed()->orWhere(fn ($h) => $h->where('status', 'expired')->whereNotNull('published_at'));
            })
            ->whereRaw("COALESCE((SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1),'') != 'rejected'");
        return app(PublicMapScope::class)->viewport(app(PublicMapScope::class)->apply($q,$school),$bbox);
    }

    public function item(Hazard $h): array
    {
        $activity = $h->last_confirmed_at ?? $h->first_reported_at;
        $historical = $h->status->value === 'expired' || ($h->status->value !== 'resolved' && $activity && $activity->lt(now()->subMinutes(3 * $h->type->ttl_minutes)));

        return ['id' => $h->public_slug, 'type' => $h->type->code, 'type_label' => __($h->type->label), 'type_visual' => app(ConditionIconService::class)->visual($h->type), 'latitude' => (float) $h->latitude, 'longitude' => (float) $h->longitude,
            'created_at' => $h->first_reported_at?->toIso8601String(), 'last_confirmed_at' => $h->last_confirmed_at?->toIso8601String(), 'status' => $h->status->value,
            'status_label' => $historical ? __('Riwayat — perlu konfirmasi ulang') : $h->status_label, 'historical' => $historical, 'title' => __($h->type->label),
            // Never send raw report descriptions, reviewer notes, actors, email or photo paths to the public map.
            'description' => $historical ? __('Catatan historis; bukan konfirmasi keadaan saat ini.') : ($h->status->value === 'resolved' ? __('Dilaporkan selesai; bukan jaminan kondisi jalan.') : __('Informasi kondisi dari laporan komunitas.')),
            'location' => $h->location_label, 'severity' => $h->severity, 'reports_count' => $h->reports_count, 'confirmations_count' => $h->confirmations_count,
            'is_demo' => $h->is_demo, 'url' => route('conditions.show', $h)];
    }

    public function page(School $school, array $filters): array
    {
        $bbox=app(PublicMapScope::class)->bounds($filters['bbox']??null);
        $scope=hash('sha256',json_encode([$school->id,app(PublicMapScope::class)->shared($school),$bbox]));
        $after = 0;
        $ceiling = Hazard::max('id') ?? 0;
        if (! empty($filters['cursor'])) {
            try {
                $c = json_decode(Crypt::decryptString($filters['cursor']), true, 16, JSON_THROW_ON_ERROR);
                if ((($c['school'] ?? null) !== $school->id || ($c['scope']??null)!==$scope) || ! isset($c['after'],$c['ceiling'])) {
                    throw new \RuntimeException;
                }$after = (int) $c['after'];
                $ceiling = (int) $c['ceiling'];
            } catch (\Throwable) {
                throw ValidationException::withMessages(['cursor' => 'Kursor map tidak valid. Muat ulang kondisi.']);
            }
        }
        $limit = (int) ($filters['limit'] ?? 200);
        $rows = $this->query($school,$bbox)->where('hazards.id', '>', $after)->where('hazards.id', '<=', $ceiling)->orderBy('hazards.id')->limit($limit + 1)->get();
        $more = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return ['items' => $rows->map(fn ($h) => $this->item($h))->values()->all(), 'next_cursor' => $more ? Crypt::encryptString(json_encode(['scope'=>$scope,'school' => $school->id, 'after' => $rows->last()->id, 'ceiling' => $ceiling])) : null,
            'school_id' => $school->id, 'public_scope'=>app(PublicMapScope::class)->shared($school)?'cross_school':'selected_school', 'coverage'=>$bbox?'viewport':'all_published', 'generated_at' => now()->toIso8601String(), 'history_policy' => 'published_persistent'];
    }
}
