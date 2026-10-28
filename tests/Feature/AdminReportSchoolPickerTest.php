<?php

namespace Tests\Feature;

use App\Models\Hazard;
use App\Models\School;
use App\Models\User;
use App\Services\DapodikSchoolDirectory;
use App\Services\SpatialSql;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class AdminReportSchoolPickerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Dedicated spatial database required.');
        }parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('s', 32)), 'cache.default' => 'array']);
        Cache::flush();
        $this->seed(DemoSeeder::class);
    }

    private function owner(): User
    {
        $u = (new User)->forceFill(['display_name' => 'Report picker QA', 'email' => 'report-picker-qa@ruteaman.my.id', 'password' => 'SyntheticPassword!123', 'role' => 'owner', 'active' => true, 'status' => 'active']);
        $u->save();

        return $u;
    }

    private function school(): School
    {
        return School::where('name', 'Sekolah A — Demo Simulasi')->firstOrFail();
    }

    public function test_owner_report_page_has_online_search_and_npsn_not_only_registered_dropdown(): void
    {
        $this->actingAs($this->owner())->get('/admin/hazards?school='.$this->school()->id)->assertOk()->assertSee('data-picker-context="reports"', false)->assertSee('Cari semua sekolah berdasarkan nama atau NPSN')->assertSee('Cari sekolah online')->assertSee('admin-school-picker.js?v=languages-1', false);
    }

    public function test_registered_other_school_can_be_displayed(): void
    {
        $b = School::where('name', 'Sekolah B — Demo Simulasi')->firstOrFail();
        $this->actingAs($this->owner())->get('/admin/hazards?school='.$b->id)->assertOk()->assertViewHas('school', fn ($s) => $s->id === $b->id)->assertSee($b->name);
    }

    public function test_school_admin_is_not_given_nationwide_import_or_out_of_scope_access(): void
    {
        $admin = User::where('email', 'admin_a@ruteaman.test')->firstOrFail();
        $this->actingAs($admin)->get('/admin/hazards')->assertOk()->assertDontSee('data-picker-context="reports"', false)->assertSee('Daftar dibatasi sekolah dalam cakupan izin akun');
        $b = School::where('name', 'Sekolah B — Demo Simulasi')->firstOrFail();
        $this->getJson('/admin/hazards?school='.$b->id)->assertForbidden();
        $this->getJson('/admin/school-picker/search?q=Sekolah')->assertForbidden();
    }

    public function test_online_search_then_explicit_registration_allows_showing_new_school_without_fake_reports(): void
    {
        $mock = Mockery::mock(DapodikSchoolDirectory::class);
        $this->app->instance(DapodikSchoolDirectory::class, $mock);
        $mock->shouldReceive('searchNationwide')->once()->with('Sekolah Nasional')->andReturn([['name' => 'Sekolah Nasional QA', 'npsn' => '10101019', 'province' => 'Jawa Tengah']]);
        $mock->shouldReceive('guestPreview')->once()->with('10101019')->andReturn(['province_code' => '33', 'regency_code' => '33.17', 'district_code' => '33.17.14']);
        $mock->shouldReceive('import')->once()->with('10101019', '33', '33.17', '33.17.14')->andReturnUsing(function () {
            $id = DB::table('schools')->insertGetId(['name' => 'Sekolah Nasional QA', 'npsn' => '10101019', 'timezone' => 'Asia/Jakarta', 'location' => SpatialSql::point(110, -7), 'catchment' => SpatialSql::expression('POLYGON((109.99 -7.01,110.01 -7.01,110.01 -6.99,109.99 -6.99,109.99 -7.01))')]);

            return School::findOrFail($id);
        });
        $this->actingAs($this->owner())->withSession(['admin.reauth_at' => time(), 'admin.last_seen' => time()]);
        $before = School::count();
        $users = User::count();
        $reports = Hazard::count();
        $this->getJson('/admin/school-picker/search?q=Sekolah%20Nasional')->assertOk()->assertJsonPath('data.0.source', 'directory');
        $this->assertSame($before, School::count());
        $this->postJson('/admin/school-picker/register', ['npsn' => '10101019'])->assertOk();
        $s = School::where('npsn', '10101019')->firstOrFail();
        $this->assertSame('paused', $s->status);
        $this->assertFalse($s->features['reporting']);
        $this->assertSame($users, User::count());
        $this->assertSame($reports, Hazard::count());
        $this->get('/admin/hazards?school='.$s->id)->assertOk()->assertViewHas('school', fn ($school) => $school->id === $s->id)->assertSee('Sekolah Nasional QA')->assertSee('Pemantauan sekolah ini dijeda')->assertSee('Direktori online menyediakan data sekolah, bukan laporan kondisi otomatis.');
    }
}
