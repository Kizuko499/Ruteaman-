<?php

namespace App\Services;

use App\Models\Hazard;
use App\Models\School;
use Illuminate\Support\Facades\DB;

final class RelatedHazardService
{
    public function __construct(private ConditionFeedService $feed) {}

    public function nearest(Hazard $hazard, ?School $school): array
    {
        $wkt = app(HazardSchoolContext::class)->pointWkt($hazard);
        if (! $wkt) {
            return [];
        }
        $point = SpatialSql::wkt();
        $distance = DB::getDriverName() === 'pgsql' ? "ST_Distance(hazards.location::geography,($point)::geography)" : "ST_Distance_Sphere(hazards.location,$point)";
        $q = $this->feed->query($school)->where('hazards.id', '!=', $hazard->id)->whereRaw($distance.' <= 1000', [$wkt])->reorder()->selectRaw($distance.' AS distance_m', [$wkt])->orderBy('distance_m')->orderByRaw(ConditionFeedService::activity().' DESC')->orderByDesc('hazards.id');

        return $q->limit(4)->get()->map(fn ($h) => $this->feed->item($h))->all();
    }
}
