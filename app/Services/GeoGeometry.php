<?php
namespace App\Services;

use Illuminate\Validation\ValidationException;

/** Small school polygons: topology validation and spherical metre calculations. */
final class GeoGeometry
{
    private const EARTH = 6371008.8;

    /**
     * Poligon lingkaran (24 sisi) di sekitar pin sebagai WKT POLYGON.
     * Dipakai untuk area wilayah bawaan institusi yang belum mempunyai catchment.
     */
    public static function circleWkt(float $lng, float $lat, int $radiusMeters): string
    {
        $radiusMeters = max(100, min(2800, $radiusMeters));
        $dLat = $radiusMeters / 111320.0;
        $dLng = $radiusMeters / (111320.0 * max(0.01, cos(deg2rad($lat))));
        $points = [];
        for ($i = 0; $i < 24; $i++) {
            $a = deg2rad($i * 15.0);
            $points[] = sprintf('%.7F %.7F', $lng + $dLng * cos($a), $lat + $dLat * sin($a));
        }
        $points[] = $points[0];

        return 'POLYGON(('.implode(',', $points).'))';
    }

    public function polygon(array|string $input, float $lng, float $lat): array
    {
        $geo = is_string($input) ? json_decode($input, true) : $input;
        $fail = static fn() => throw ValidationException::withMessages([
            'catchment' => 'Catchment harus Polygon GeoJSON satu ring, tertutup, tidak berpotongan, mencakup pin sekolah, dan luas maksimal 25 km².',
        ]);
        if (!is_array($geo) || ($geo['type'] ?? '') !== 'Polygon' || !is_array($geo['coordinates'] ?? null)
            || !array_is_list($geo['coordinates']) || count($geo['coordinates']) !== 1) $fail();
        $ring = $geo['coordinates'][0];
        if (!is_array($ring) || !array_is_list($ring) || count($ring) < 4 || count($ring) > 500) $fail();
        foreach ($ring as &$p) {
            if (!is_array($p) || !array_is_list($p) || count($p) !== 2 || !is_numeric($p[0]) || !is_numeric($p[1])) $fail();
            $p = [(float)$p[0], (float)$p[1]];
            if (!is_finite($p[0]) || !is_finite($p[1]) || abs($p[0]) > 180 || abs($p[1]) > 90) $fail();
        }
        unset($p);
        if (!is_finite($lng) || !is_finite($lat) || abs($lng) > 180 || abs($lat) > 90 || $ring[0] !== $ring[array_key_last($ring)]) $fail();
        $n = count($ring) - 1;
        // This school catchment format does not support dateline-wrapping polygons.
        if (max(array_column($ring, 0)) - min(array_column($ring, 0)) > 180) $fail();
        if (count(array_unique(array_map(fn($p) => json_encode($p), array_slice($ring, 0, -1)))) !== $n) $fail();
        for ($i = 0; $i < $n; $i++) {
            if ($ring[$i] === $ring[$i+1]) $fail();
            // Collinear reversals produce overlapping adjacent edges.
            $prev = $ring[($i + $n - 1) % $n];
            $next = $ring[$i+1];
            if (abs($this->cross($prev, $ring[$i], $next)) < 1e-14
                && (($prev[0]-$ring[$i][0])*($next[0]-$ring[$i][0]) + ($prev[1]-$ring[$i][1])*($next[1]-$ring[$i][1])) > 0) $fail();
            for ($j = $i+1; $j < $n; $j++) {
                if ($j === $i+1 || ($i === 0 && $j === $n-1)) continue;
                if ($this->intersects($ring[$i], $ring[$i+1], $ring[$j], $ring[$j+1])) $fail();
            }
        }
        $area = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $area += deg2rad($ring[$i+1][0]-$ring[$i][0])
                * (sin(deg2rad($ring[$i][1])) + sin(deg2rad($ring[$i+1][1])));
        }
        $area = abs($area) * self::EARTH ** 2 / 2;
        if ($area <= 0.01 || $area > 25000000 || !$this->covers($ring, [$lng,$lat])) $fail();
        return ['geojson' => json_encode(['type'=>'Polygon','coordinates'=>[$ring]], JSON_THROW_ON_ERROR),
            'wkt' => 'POLYGON(('.implode(',', array_map(fn($p)=>$p[0].' '.$p[1], $ring)).'))', 'area_m2'=>$area];
    }

    private function cross(array $a, array $b, array $c): float
    {
        return ($b[0]-$a[0])*($c[1]-$a[1])-($b[1]-$a[1])*($c[0]-$a[0]);
    }

    private function onSegment(array $a, array $b, array $p): bool
    {
        return abs($this->cross($a,$b,$p)) < 1e-14
            && $p[0] >= min($a[0],$b[0])-1e-12 && $p[0] <= max($a[0],$b[0])+1e-12
            && $p[1] >= min($a[1],$b[1])-1e-12 && $p[1] <= max($a[1],$b[1])+1e-12;
    }

    private function intersects(array $a, array $b, array $c, array $d): bool
    {
        $x=$this->cross($a,$b,$c);$y=$this->cross($a,$b,$d);$z=$this->cross($c,$d,$a);$w=$this->cross($c,$d,$b);
        return (($x>0 && $y<0 || $x<0 && $y>0) && ($z>0 && $w<0 || $z<0 && $w>0))
            || $this->onSegment($a,$b,$c) || $this->onSegment($a,$b,$d)
            || $this->onSegment($c,$d,$a) || $this->onSegment($c,$d,$b);
    }

    private function covers(array $ring, array $p): bool
    {
        $inside=false;
        for ($i=0; $i<count($ring)-1; $i++) {
            [$a,$b]=[$ring[$i],$ring[$i+1]];
            if ($this->onSegment($a,$b,$p)) return true;
            if (($a[1]>$p[1]) !== ($b[1]>$p[1]) && $p[0]<($b[0]-$a[0])*($p[1]-$a[1])/($b[1]-$a[1])+$a[0]) $inside=!$inside;
        }
        return $inside;
    }

    public function routeDistance(float $lng, float $lat, array $line): float
    {
        if (($line['type']??'') !== 'LineString' || count($line['coordinates']??[])<2) {
            throw new \InvalidArgumentException('Rute harus LineString.');
        }
        $min=INF;$coords=$line['coordinates'];
        for ($i=0;$i<count($coords)-1;$i++) {
            $a=$coords[$i];$b=$coords[$i+1];
            $d12=$this->angular($a,$b);
            $d13=$this->angular($a,[$lng,$lat]);
            $diff=$this->bearing($a,[$lng,$lat])-$this->bearing($a,$b);
            $along=atan2(sin($d13)*cos($diff),cos($d13));
            $cross=asin(max(-1,min(1,sin($d13)*sin($diff))));
            $distance=$d12>0 && $along>=0 && $along<=$d12 ? abs($cross)*self::EARTH
                : min($d13,$this->angular($b,[$lng,$lat]))*self::EARTH;
            $min=min($min,$distance);
        }
        return $min;
    }

    private function angular(array $a, array $b): float
    {
        $h=sin(deg2rad($b[1]-$a[1])/2)**2+cos(deg2rad($a[1]))*cos(deg2rad($b[1]))*sin(deg2rad($b[0]-$a[0])/2)**2;
        return 2*asin(sqrt(max(0,min(1,$h))));
    }

    private function bearing(array $a, array $b): float
    {
        $dl=deg2rad($b[0]-$a[0]);$la=deg2rad($a[1]);$lb=deg2rad($b[1]);
        return atan2(sin($dl)*cos($lb),cos($la)*sin($lb)-sin($la)*cos($lb)*cos($dl));
    }
}
