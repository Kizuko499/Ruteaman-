<?php

namespace App\Services;

use App\Models\Hazard;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HazardSchoolContext
{
    public function pointWkt(Hazard $h): ?string
    {
        $row = DB::table('hazards')->where('id', $h->id)->selectRaw(SpatialSql::coordinates('lat', 'lng'))->first();
        if (! $row || ! is_numeric($row->lat) || ! is_numeric($row->lng)) {
            return null;
        }
        $lat = (float) $row->lat;
        $lng = (float) $row->lng;
        if (! is_finite($lat) || ! is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) {
            return null;
        }

        return "POINT($lng $lat)";
    }

    public function pointSchools(Hazard $h)
    {
        $wkt = $this->pointWkt($h);
        $q = School::query();

        return $wkt ? $q->whereRaw('ST_Intersects(catchment,'.SpatialSql::wkt().')', [$wkt]) : $q->whereRaw('1=0');
    }

    public function associatedIds(Hazard $h): array
    {
        return DB::table('hazard_reports as r')->leftJoin('users as u', 'u.id', '=', 'r.reporter_id')->where('r.hazard_id', $h->id)->selectRaw('COALESCE(r.school_id,u.school_id) AS school_id')->pluck('school_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function reviewSchools(Hazard $h): Collection
    {
        $ids = $this->associatedIds($h);

        return $ids ? School::whereIn('id', $ids)->orderBy('id')->get() : $this->pointSchools($h)->orderBy('id')->get();
    }
}
