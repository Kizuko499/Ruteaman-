<?php

namespace Tests\Unit;

use App\Support\InstitutionDuplicateFinder;
use PHPUnit\Framework\TestCase;

class InstitutionDuplicateFinderTest extends TestCase
{
    public function test_groups_by_npsn_and_name_coords_without_double_listing(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Aisyah University', 'npsn' => '70000001', 'lat' => -5.4221, 'lng' => 104.9345],
            ['id' => 2, 'name' => 'Aisyah University', 'npsn' => '70000001', 'lat' => -5.4221, 'lng' => 104.9345],
            ['id' => 3, 'name' => 'Akademi Mataram', 'npsn' => null, 'lat' => -8.5833, 'lng' => 116.1167],
            ['id' => 4, 'name' => 'akademi  mataram', 'npsn' => '', 'lat' => -8.58331, 'lng' => 116.11669],
            ['id' => 5, 'name' => 'Unik School', 'npsn' => '70000002', 'lat' => -6.1, 'lng' => 106.8],
        ];
        $groups = InstitutionDuplicateFinder::groups($rows);
        $this->assertCount(2, $groups);
        $this->assertSame('npsn', $groups[0]['kind']);
        $this->assertSame([1, 2], $groups[0]['member_ids']);
        $this->assertSame('name_coords', $groups[1]['kind']);
        $this->assertSame([3, 4], $groups[1]['member_ids']);
    }

    public function test_name_normalization(): void
    {
        $this->assertSame('sma negeri 1', InstitutionDuplicateFinder::normalizeName('  SMA   Negeri 1 '));
    }
}
