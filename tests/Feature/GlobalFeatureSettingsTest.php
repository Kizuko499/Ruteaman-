<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\School;
use App\Models\User;
use App\Services\DapodikSchoolDirectory;
use App\Services\GlobalFeatureSettings;
use App\Services\GuestReportService;
use App\Services\GuestSchoolSelection;
use App\Services\PermissionService;
use App\Services\ReportPublicationPolicy;
use App\Services\SchoolProvisioningService;
use App\Services\SpatialSql;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalFeatureSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Dedicated spatial database required.');
        }parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('w', 32)), 'cache.default' => 'array']);
        Cache::flush();
        $this->seed(DemoSeeder::class);
    }

    private function owner(): User
    {
        $u = (new User)->forceFill(['display_name' => 'General QA', 'email' => 'general-qa@ruteaman.my.id', 'password' => 'SyntheticOnly!123', 'role' => 'owner', 'status' => 'active', 'active' => true]);
        $u->save();

        return $u;
    }

    private function school(string $letter = 'A'): School
    {
        return School::where('name', 'Sekolah '.$letter.' — Demo Simulasi')->firstOrFail();
    }

    private function authOwner(): User
    {
        $u = $this->owner();
        $this->actingAs($u)->withSession(['admin.reauth_at' => time(), 'admin.last_seen' => time()]);

        return $u;
    }

    private function profile(array $overrides = []): array
    {
        return [...config('ruteaman_admin.features'), ...$overrides];
    }

    private function newSchool(): School
    {
        $id = DB::table('schools')->insertGetId(['name' => 'New general school QA', 'public_slug' => 'new-general-qa', 'timezone' => 'Asia/Jakarta', 'location' => SpatialSql::point(110, -7), 'catchment' => SpatialSql::expression('POLYGON((109.98 -7.02,110.02 -7.02,110.02 -6.98,109.98 -6.98,109.98 -7.02))'), 'features' => null]);

        return School::findOrFail($id);
    }

    public function test_global_panel_is_primary_and_parent_feature_stays_removed(): void
    {
        $this->authOwner();
        $this->get('/admin/features')->assertOk()->assertSee('Pengaturan umum · seluruh sekolah')->assertSee('Simpan & terapkan secara umum')->assertSee('id="general-features"', false)->assertSee('Pengaturan khusus per sekolah')->assertDontSee('Tampilan orang tua')->assertDontSee('features[parent_view]');
    }

    public function test_general_save_updates_both_schools_persists_defaults_and_is_audited(): void
    {
        $this->authOwner();
        $this->patchJson('/admin/features/general', ['features' => $this->profile(['require_guest_review' => true, 'require_account_review' => false, 'trend' => true])])->assertOk();
        foreach (['A', 'B'] as $letter) {
            $s = $this->school($letter);
            $this->assertTrue($s->features['require_guest_review']);
            $this->assertFalse($s->features['require_account_review']);
            $this->assertTrue($s->features['trend']);
        }
        $this->assertTrue(app(GlobalFeatureSettings::class)->enabled('require_guest_review'));
        $this->assertNotNull(app(GlobalFeatureSettings::class)->updatedAt());
        $this->assertTrue(AuditLog::where('action', 'platform.features_updated')->exists());
        $new = $this->newSchool();
        $this->assertTrue(app(ReportPublicationPolicy::class)->requiresReview($new, true));
        $this->assertFalse(app(ReportPublicationPolicy::class)->requiresReview($new, false));
        $this->assertTrue(app(PermissionService::class)->feature($new, 'trend'));
    }

    public function test_new_guest_school_uses_stored_global_review_mode(): void
    {
        $this->authOwner();
        $this->patchJson('/admin/features/general', ['features' => $this->profile(['require_guest_review' => true, 'require_account_review' => false])])->assertOk();
        $new = $this->newSchool();
        app(GuestReportService::class)->submit(['school_id' => $new->id, 'type_id' => HazardType::where('code', 'flood')->value('id'), 'latitude' => -7, 'longitude' => 110], null);
        $h = Hazard::latest('id')->firstOrFail();
        $this->assertTrue($h->requires_moderation);
        $this->assertNull($h->published_at);
        $this->assertSame($new->id, $h->reports()->first()->school_id);
    }

    public function test_school_exception_does_not_change_global_and_next_general_save_replaces_it(): void
    {
        $u = $this->authOwner();
        app(GlobalFeatureSettings::class)->apply($u, $this->profile(['require_guest_review' => true]));
        app(SchoolProvisioningService::class)->features($u, $this->school(), ['require_guest_review' => false]);
        $this->assertFalse($this->school()->features['require_guest_review']);
        $this->assertTrue($this->school('B')->features['require_guest_review']);
        $this->assertTrue(app(GlobalFeatureSettings::class)->enabled('require_guest_review'));
        app(GlobalFeatureSettings::class)->apply($u, $this->profile(['require_guest_review' => true]));
        $this->assertTrue($this->school()->features['require_guest_review']);
    }

    public function test_global_save_does_not_activate_paused_or_archived_school(): void
    {
        $u = $this->authOwner();
        $this->school()->forceFill(['status' => 'paused'])->save();
        $this->school('B')->forceFill(['status' => 'archived'])->save();
        app(GlobalFeatureSettings::class)->apply($u, $this->profile(['reporting' => true]));
        $a = $this->school();
        $this->assertSame('paused', $a->status);
        $this->assertSame('archived', $this->school('B')->status);
        $this->assertFalse(app(PermissionService::class)->feature($a, 'reporting', true));
    }

    public function test_global_reporting_off_blocks_existing_and_new_schools(): void
    {
        $u = $this->authOwner();
        app(GlobalFeatureSettings::class)->apply($u, $this->profile(['reporting' => false, 'shared_layer' => false]));
        $this->assertFalse(app(PermissionService::class)->feature($this->school(), 'reporting'));
        $new = $this->newSchool();
        $this->assertFalse(app(PermissionService::class)->feature($new, 'reporting'));
        $response = $this->get('/map?school='.$new->id)->assertOk();
        preg_match('/<script type="application\/json" id="school-map-data">(.*?)<\/script>/s', $response->getContent(), $match);
        $data = collect(json_decode($match[1], true))->firstWhere('id', $new->id);
        $this->assertFalse($data['shared_layer']);
        $this->assertFalse(app(GuestSchoolSelection::class)->reporting($this->school()));
    }

    public function test_invalid_review_configuration_rolls_back_every_school_and_defaults(): void
    {
        $this->authOwner();
        $before = DB::table('platform_feature_settings')->value('features');
        $a = $this->school()->features;
        $b = $this->school('B')->features;
        $this->patchJson('/admin/features/general', ['features' => $this->profile(['require_guest_review' => true, 'verification' => false])])->assertUnprocessable();
        $this->assertSame($before, DB::table('platform_feature_settings')->value('features'));
        $this->assertSame($a, $this->school()->features);
        $this->assertSame($b, $this->school('B')->features);
    }

    public function test_general_endpoint_requires_owner_reauth_complete_profile_and_known_keys(): void
    {
        $admin = User::where('email', 'admin_a@ruteaman.test')->firstOrFail();
        $this->actingAs($admin)->withSession(['admin.reauth_at' => time(), 'admin.last_seen' => time()])->patchJson('/admin/features/general', ['features' => $this->profile()])->assertForbidden();
        $this->authOwner();
        $this->withSession(['admin.reauth_at' => 0])->patchJson('/admin/features/general', ['features' => $this->profile()])->assertStatus(428);
        $this->withSession(['admin.reauth_at' => time()])->patchJson('/admin/features/general', ['features' => ['reporting' => false]])->assertUnprocessable();
        $this->patchJson('/admin/features/general', ['features' => $this->profile(['parent_view' => true])])->assertUnprocessable();
    }

    public function test_legacy_bulk_action_affects_existing_only_and_does_not_change_new_school_default(): void
    {
        $u = $this->authOwner();
        $before = app(GlobalFeatureSettings::class)->defaults();
        app(SchoolProvisioningService::class)->all($u, ['trend' => true]);
        $this->assertTrue($this->school()->features['trend']);
        $this->assertSame($before, app(GlobalFeatureSettings::class)->defaults());
        $this->assertFalse(app(PermissionService::class)->feature($this->newSchool(), 'trend'));
    }

    public function test_old_pending_reports_are_not_published_when_general_review_is_disabled(): void
    {
        $u = $this->authOwner();
        $this->school()->forceFill(['features' => $this->profile(['require_guest_review' => true])])->save();
        app(GuestReportService::class)->submit(['school_id' => $this->school()->id, 'type_id' => HazardType::where('code', 'flood')->value('id'), 'latitude' => -6.1768, 'longitude' => 106.825], null);
        $h = Hazard::latest('id')->firstOrFail();
        app(GlobalFeatureSettings::class)->apply($u, $this->profile(['require_guest_review' => false]));
        $this->assertTrue($h->fresh()->requires_moderation);
        $this->assertNull($h->fresh()->published_at);
    }

    public function test_owner_directory_registration_inherits_defaults_but_stays_paused_and_reporting_off(): void
    {
        $u = $this->authOwner();
        app(GlobalFeatureSettings::class)->apply($u, $this->profile(['require_guest_review' => true, 'avatar_upload' => true]));
        $directory = $this->mock(DapodikSchoolDirectory::class);
        $directory->shouldReceive('guestPreview')->once()->with('10101456')->andReturn(['province_code' => '33', 'regency_code' => '33.17', 'district_code' => '33.17.14']);
        $directory->shouldReceive('import')->once()->with('10101456', '33', '33.17', '33.17.14')->andReturnUsing(function () {
            $s = $this->newSchool();
            $s->forceFill(['npsn' => '10101456'])->save();

            return $s;
        });
        $this->postJson('/admin/school-picker/register', ['npsn' => '10101456'])->assertOk();
        $s = School::where('npsn', '10101456')->firstOrFail();
        $this->assertSame('paused', $s->status);
        $this->assertFalse($s->features['reporting']);
        $this->assertTrue($s->features['require_guest_review']);
        $this->assertTrue($s->features['avatar_upload']);
    }
}
