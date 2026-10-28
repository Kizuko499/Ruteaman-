<?php

namespace Tests\Unit;

use App\Services\GeoGeometry;
use PHPUnit\Framework\TestCase;

final class CircleCatchmentTest extends TestCase
{
    public function test_circle_wkt_is_a_closed_25_point_ring_around_the_pin(): void
    {
        $wkt = GeoGeometry::circleWkt(111.451965, -6.6935544, 2000);
        $this->assertStringStartsWith('POLYGON((', $wkt);
        $this->assertStringEndsWith('))', $wkt);
        $points = explode(',', substr($wkt, 9, -2));
        $this->assertCount(25, $points);
        $this->assertSame($points[0], $points[24]);
    }

    public function test_circle_reaches_about_the_requested_radius_east(): void
    {
        $lng = 111.451965;
        $lat = -6.6935544;
        $wkt = GeoGeometry::circleWkt($lng, $lat, 2000);
        $first = explode(' ', explode(',', substr($wkt, 9, -2))[0]);
        $eastMeters = ((float) $first[0] - $lng) * 111320 * cos(deg2rad($lat));
        $this->assertEqualsWithDelta(2000.0, $eastMeters, 25.0);
        $this->assertEqualsWithDelta($lat, (float) $first[1], 0.0000001);
    }
}
