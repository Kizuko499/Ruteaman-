<?php

namespace Tests\Feature;

use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\School;
use App\Models\User;
use App\Services\DapodikSchoolDirectory;
use App\Services\GlobalFeatureSettings;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuestSchoolSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Dedicated spatial database required.');
        }
        parent::setUp();
        config(['ruteaman_admin.features.require_guest_review' => true, 'app.key' => 'base64:'.base64_encode(str_repeat('s', 32)), 'cache.default' => 'array', 'services.dapodik.token' => 'qa-public']);
        Cache::flush();
        if (Schema::hasTable('platform_feature_settings')) {
            DB::table('platform_feature_settings')->where('id', 1)->update(['features' => json_encode(config('ruteaman_admin.features'))]);
            app(GlobalFeatureSettings::class)->forget();
        }
        $this->seed(DemoSeeder::class);
        School::query()->each(fn ($school) => $school->forceFill(['features' => [...($school->features ?? []), 'require_guest_review' => true]])->save());
        Cache::put('school.directory.online-provinces.v1', [['code' => '33', 'name' => 'Jawa Tengah']]);
        Cache::put('school.directory.online-regencies.v1.33', [['code' => '33.17', 'name' => 'Kabupaten Rembang']]);
        Cache::put('school.directory.online-districts.v1.33.17', [['code' => '33.17.14', 'name' => 'Lasem']]);
        Http::preventStrayRequests();
        Http::fake(['https://dapo.kemendikdasmen.go.id/*' => Http::response(['data' => []]), 'https://sekolah.devapi.id/*' => Http::response(json_decode(file_get_contents(__DIR__.'/../Fixtures/man-2-rembang.json'), true))]);
    }

    private function preview(): array
    {
        return $this->getJson('/lapor?school_npsn=20363192')->assertOk()->json();
    }

    private function form(string $ticket, array $overrides = []): array
    {
        $this->get('/lapor')->assertOk();
        $token = session('guest_report_form.token');
        $this->travel(3)->seconds();

        return [...['school_id' => '', 'school_ticket' => $ticket, 'type_id' => HazardType::where('code', 'flood')->value('id'), 'latitude' => -6.6935544, 'longitude' => 111.451965, 'report_consent' => 1, '_form_token' => $token], ...$overrides];
    }

    public function test_form_searches_nationwide_without_auth_or_route_replacement(): void
    {
        $this->get('/lapor')->assertOk()->assertSee('Cari nama sekolah atau NPSN')->assertSee('guest-school-query', false);
        $this->getJson('/lapor?school_query=MAN%202%20REMBANG')->assertOk()->assertJsonPath('data.0.npsn', '20363192');
        $this->getJson('/lapor?school_query=20363192')->assertOk()->assertJsonPath('data.0.name', 'MAN 2 REMBANG');
    }

    public function test_preview_has_coordinates_and_region_without_database_writes(): void
    {
        $schools = School::count();
        $users = User::count();
        $d = $this->preview();
        $this->assertSame('33.17.14', $d['data']['district_code']);
        $this->assertSame('Polygon', $d['data']['catchment']['type']);
        $this->assertEqualsWithDelta(-6.6935544, $d['data']['latitude'], .000001);
        $this->assertNotEmpty($d['ticket']);
        $this->assertSame($schools, School::count());
        $this->assertSame($users, User::count());
    }

    public function test_unregistered_school_is_recorded_only_with_valid_pending_report(): void
    {
        $users = User::count();
        $schools = School::count();
        $d = $this->preview();
        $this->post('/lapor', $this->form($d['ticket']))->assertRedirect(route('guest-reports.receipt'));
        $this->assertNotEmpty(School::where('npsn', '20363192')->value('public_slug'));
        $this->assertSame($schools + 1, School::count());
        $this->assertSame($users, User::count());
        $h = Hazard::where('requires_moderation', true)->firstOrFail();
        $this->assertNull($h->published_at);
        $this->assertSame(School::where('npsn', '20363192')->value('id'), $h->reports()->first()->school_id);
        $this->assertTrue($h->reports()->first()->is_guest);
        $this->assertNull($h->reports()->first()->reporter_id);
    }

    public function test_invalid_location_does_not_create_school(): void
    {
        $d = $this->preview();
        $schools = School::count();
        $this->post('/lapor', $this->form($d['ticket'], ['longitude' => 106]))->assertSessionHasErrors('latitude');
        $this->assertSame($schools, School::count());
        $this->assertFalse(Hazard::where('requires_moderation', true)->exists());
    }

    public function test_failed_validation_restores_verified_school_choice(): void
    {
        $d = $this->preview();
        $this->post('/lapor', $this->form($d['ticket'], ['type_id' => 999999]))->assertSessionHasErrors('type_id');
        $this->get('/lapor')->assertOk()->assertSee('MAN 2 REMBANG')->assertSee('20363192');
        $this->assertNull(School::where('npsn', '20363192')->first());
    }

    public function test_tampered_expired_and_cross_session_tickets_are_rejected(): void
    {
        $d = $this->preview();
        $this->post('/lapor', $this->form($d['ticket'].'tampered'))->assertSessionHasErrors('school_ticket');
        $this->travel(3)->hours();
        $this->post('/lapor', $this->form($d['ticket']))->assertSessionHasErrors('school_ticket');
        $fresh = $this->preview();
        $this->withSession(['guest_school_nonce' => str_repeat('x', 40)]);
        $this->post('/lapor', $this->form($fresh['ticket']))->assertSessionHasErrors('school_ticket');
    }

    public function test_free_text_school_or_conflicting_local_id_cannot_be_submitted(): void
    {
        $this->post('/lapor', $this->form('', ['school_name' => 'Fabricated school', 'school_npsn' => '87654321']))->assertSessionHasErrors('school_id');
        $d = $this->preview();
        $this->post('/lapor', $this->form($d['ticket'], ['school_id' => School::first()->id]))->assertSessionHasErrors('school_id');
    }

    public function test_disabled_school_is_not_reenabled_by_directory_or_stale_ticket(): void
    {
        $d = $this->preview();
        $s = app(DapodikSchoolDirectory::class)->import('20363192', '33', '33.17', '33.17.14');
        $s->forceFill(['status' => 'paused'])->save();
        $this->getJson('/lapor?school_query=20363192')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/lapor?school_npsn=20363192')->assertStatus(422);
        $this->postJson('/lapor', $this->form($d['ticket']))->assertForbidden();
        $this->assertSame('paused', $s->fresh()->status);
    }

    public function test_registered_school_fallback_survives_national_outage(): void
    {
        $s = School::where('name', 'Sekolah A — Demo Simulasi')->firstOrFail();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['message' => 'Unavailable'], 503)]);
        $this->getJson('/lapor?school_query=Sekolah%20A')->assertOk()->assertJsonPath('data.0.id', $s->id)->assertJsonPath('data.0.source', 'registered')->assertSee('tidak dapat dihubungi');
    }

    public function test_search_validation_and_rate_limit(): void
    {
        $this->getJson('/lapor?school_query=a')->assertUnprocessable();
        $this->getJson('/lapor?school_npsn=bad')->assertUnprocessable();
        for ($i = 0; $i < 28; $i++) {
            $this->getJson('/lapor?school_query=20363192')->assertOk();
        }
        $this->getJson('/lapor?school_query=20363192')->assertStatus(429)->assertHeader('Retry-After', '60');
    }

    public function test_missing_coordinates_never_enables_submit_for_new_school(): void
    {
        $fixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/man-2-rembang.json'), true);
        $fixture['data'][0]['lokasi']['lintang'] = 0;
        $fixture['data'][0]['lokasi']['bujur'] = 0;
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://dapo.kemendikdasmen.go.id/*' => Http::response(['data' => []]), 'https://sekolah.devapi.id/*' => Http::response($fixture)]);
        $schools = School::count();
        $this->getJson('/lapor?school_npsn=20363192')->assertStatus(503);
        $this->assertSame($schools, School::count());
    }
}
