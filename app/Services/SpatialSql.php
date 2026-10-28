<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Database\Query\Expression;

/** WGS84 longitude/latitude SQL, including MariaDB accessed via mysql driver. */
final class SpatialSql
{
    public static function isMariaDb(): bool
    {
        return DB::getDriverName() === 'mariadb'
            || (DB::getDriverName() === 'mysql' && DB::connection()->isMaria());
    }

    public static function wkt(string $placeholder = '?'): string
    {
        if (DB::getDriverName() === 'pgsql' || self::isMariaDb()) {
            return "ST_GeomFromText($placeholder,4326)";
        }
        return "ST_GeomFromText($placeholder,4326,'axis-order=long-lat')";
    }

    public static function expression(string $wkt): Expression
    {
        return DB::raw(self::wkt(DB::getPdo()->quote($wkt)));
    }

    public static function point(float $lng, float $lat): Expression
    {
        if (!is_finite($lng) || !is_finite($lat) || $lng < -180 || $lng > 180 || $lat < -90 || $lat > 90) {
            throw new \InvalidArgumentException('Koordinat tidak valid.');
        }
        return self::expression("POINT($lng $lat)");
    }

    public static function coordinates(string $lat = 'latitude', string $lng = 'longitude'): string
    {
        $cartesian = DB::getDriverName() === 'pgsql' || self::isMariaDb();
        return ($cartesian ? 'ST_Y(location)' : 'ST_Latitude(location)')." AS $lat,"
            .($cartesian ? 'ST_X(location)' : 'ST_Longitude(location)')." AS $lng";
    }
}
