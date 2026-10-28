<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicSchoolSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('npsn')->nullable();
            $t->string('public_slug')->nullable();
            $t->string('status')->default('active');
            $t->string('institution_type')->default('school');
        });
        DB::table('schools')->insert([
            ['name' => 'MAN 2 Rembang', 'npsn' => '20363192', 'public_slug' => 'man-2-rembang', 'status' => 'active', 'institution_type' => 'school'],
            ['name' => 'MA Negeri 1 Lasem', 'npsn' => '20363193', 'public_slug' => 'ma-negeri-1-lasem', 'status' => 'active', 'institution_type' => 'school'],
            ['name' => 'MAN Arsip Lama', 'npsn' => '99999999', 'public_slug' => 'man-arsip', 'status' => 'archived', 'institution_type' => 'school'],
            ['name' => 'Universitas Terbuka', 'npsn' => null, 'public_slug' => 'universitas-terbuka', 'status' => 'active', 'institution_type' => 'campus'],
        ]);
    }

    public function test_search_by_name_returns_slug_and_meta_without_archived(): void
    {
        $r = $this->getJson('/api/schools/search?q=MAN');
        $r->assertOk();
        $names = collect($r->json())->pluck('name');
        $this->assertTrue($names->contains('MAN 2 Rembang'));
        $this->assertFalse($names->contains('MAN Arsip Lama'));
        $first = collect($r->json())->firstWhere('name', 'MAN 2 Rembang');
        $this->assertSame('man-2-rembang', $first['slug']);
        $this->assertNotEmpty($first['id']);
        $this->assertSame('NPSN 20363192', $first['meta']);
    }

    public function test_search_by_npsn_and_short_query(): void
    {
        $this->getJson('/api/schools/search?q=20363193')->assertOk()->assertJsonFragment(['name' => 'MA Negeri 1 Lasem']);
        $this->getJson('/api/schools/search?q=M')->assertOk()->assertExactJson([]);
        $r = $this->getJson('/api/schools/search?q=Universitas');
        $this->assertSame('Kampus', $r->json()[0]['meta']);
    }
}
