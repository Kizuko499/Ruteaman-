<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\AdminInvitationMail;
use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\School;
use App\Models\User;
use App\Models\Verification;
use App\Services\AdminAccountService;
use App\Services\DapodikSchoolDirectory;
use App\Services\GeoGeometry;
use App\Services\PermissionService;
use App\Services\SchoolProvisioningService;
use App\Services\SpatialSql;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TieredAdminTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    private User $platform;

    private User $admin;

    private School $a;

    private School $b;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Dedicated spatial database required.');
        }parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32)), 'mail.default' => 'smtp']);
        Mail::fake();
        foreach (['a', 'b'] as $key) {
            $lng = $key === 'a' ? 106.82 : 107.02;
            $json = json_encode(['type' => 'Polygon', 'coordinates' => [[[$lng - .01, -6.18], [$lng + .01, -6.18], [$lng + .01, -6.16], [$lng - .01, -6.16], [$lng - .01, -6.18]]]]);
            $id = DB::table('schools')->insertGetId(['name' => 'Admin School '.$key, 'public_slug' => 'admin-school-'.$key, 'location' => SpatialSql::point($lng, -6.17), 'catchment' => SpatialSql::expression(app(GeoGeometry::class)->polygon($json, $lng, -6.17)['wkt']), 'features' => json_encode(config('ruteaman_admin.features'))]);
            $this->{$key} = School::findOrFail($id);
        }
        // No production model auto-grants access: explicit privileged fixture only.
        $this->owner = $this->user('chief', 'owner');
        $this->platform = $this->user('operator', 'platform_admin', null, config('ruteaman_roles.ceilings.platform_admin'), [$this->a->id]);
        $this->admin = $this->user('principal', 'school_admin', $this->a->id, config('ruteaman_roles.ceilings.school_admin'));
    }

    private function user(string $name, string $role, ?int $school = null, array $permissions = [], ?array $scope = null): User
    {
        $u = (new User)->forceFill(['display_name' => $name, 'email' => $name.'@ruteaman.my.id', 'contact_email' => $name.'@contact.invalid', 'password' => 'FixtureAdminPassword123!', 'role' => $role, 'school_id' => $school, 'permissions' => $permissions, 'school_scope' => $scope, 'status' => 'active', 'active' => true]);
        $u->save();

        return $u;
    }

    private function payload(string $name = 'new-person', string $role = 'school_admin'): array
    {
        return ['display_name' => $name, 'email' => $name.'.pengelola@ruteaman.my.id', 'contact_email' => $name.'@contact.invalid', 'role' => $role, 'school_id' => $this->a->id, 'permissions' => ['escalations.manage']];
    }

    private function auth(User $user, bool $reauth = true)
    {
        $this->actingAs($user)->withSession(['admin.session_version' => $user->session_version, 'admin.reauth_at' => $reauth ? time() : 0]);

        return $this;
    }

    public function test_console_requires_admin_and_sensitive_routes_require_recent_reauth(): void
    {
        $student = $this->user('pupil', 'student', $this->a->id);
        $this->auth($student)->getJson('/admin')->assertForbidden();
        $this->auth($this->owner, false)->postJson('/admin/admins', $this->payload())->assertStatus(428)->assertJsonPath('error.code', 'REAUTH_REQUIRED');
        $this->postJson('/admin/reauth', ['password' => 'wrong'])->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs', ['action' => 'reauth.failed']);
        $this->postJson('/admin/reauth', ['password' => 'FixtureAdminPassword123!'])->assertOk();
        $this->postJson('/admin/admins', $this->payload())->assertOk();
    }

    public function test_hierarchy_and_grant_ceiling_are_checked_on_server(): void
    {
        $this->auth($this->admin)->postJson('/admin/admins', $this->payload('peer', 'school_admin'))->assertForbidden();
        $this->postJson('/admin/admins', [...$this->payload(), 'permissions' => ['users.manage_admins']])->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN_SCOPE');
        $this->admin->forceFill(['permissions' => ['users.view', 'users.manage_admins']])->save();
        $this->auth($this->admin)->postJson('/admin/admins', $this->payload('ceiling'))->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN_SCOPE');
    }

    public function test_scope_on_user_school_and_platform_scope_cannot_be_expanded(): void
    {
        $other = $this->user('other-principal', 'school_admin', $this->b->id, config('ruteaman_roles.ceilings.school_admin'));
        $this->auth($this->platform)->getJson('/admin/schools/'.$this->b->id)->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN_SCOPE');
        $this->patchJson('/admin/admins/'.$other->id.'/permissions', ['permissions' => []])->assertForbidden();
        $this->postJson('/admin/admins', [...$this->payload(), 'school_id' => $this->b->id])->assertForbidden();
        $this->auth($this->admin)->get('/admin/schools/'.$this->b->id)->assertForbidden();
        $this->auth($this->platform)->get('/admin/admins')->assertOk()->assertDontSee('other-principal');
    }

    public function test_self_access_and_last_owner_protection(): void
    {
        // Ensure this fixture is the only active Owner within this rollback-isolated test.
        User::where('role', 'owner')->whereKeyNot($this->owner->id)->update(['status' => 'suspended', 'active' => false]);
        $this->auth($this->owner)->patchJson('/admin/admins/'.$this->owner->id.'/permissions', ['permissions' => []])->assertForbidden();
        $this->postJson('/admin/admins/'.$this->owner->id.'/suspend')->assertForbidden()->assertJsonPath('error.code', 'LAST_OWNER');
        $this->deleteJson('/admin/admins/'.$this->owner->id)->assertForbidden()->assertJsonPath('error.code', 'LAST_OWNER');
        $this->deleteJson('/account', ['current_password' => 'FixtureAdminPassword123!', 'confirmation' => 'HAPUS'])->assertForbidden()->assertJsonPath('error.code', 'LAST_OWNER');
    }

    public function test_owner_creation_cap_includes_pending_invitations(): void
    {
        User::where('role', 'owner')->whereKeyNot($this->owner->id)->update(['status' => 'suspended', 'active' => false]);
        config(['ruteaman_admin.owner_max' => 2]);
        $this->auth($this->owner)->postJson('/admin/admins', [...$this->payload('second-chief', 'owner'), 'school_id' => null, 'permissions' => []])->assertOk();
        $this->postJson('/admin/admins', [...$this->payload('third-chief', 'owner'), 'school_id' => null, 'permissions' => []])->assertUnprocessable();
    }

    public function test_dependencies_child_adds_parent_and_parent_off_removes_descendants(): void
    {
        $p = app(PermissionService::class);
        $this->assertEqualsCanonicalizing(['escalations.manage', 'escalations.view', 'hazards.view'], $p->normalize(['escalations.manage']));
        $this->assertSame([], $p->normalize(['escalations.manage', 'escalations.view'], ['hazards.view', 'escalations.view', 'escalations.manage']));
        $staff = $this->user('limited-staff', 'school_staff', $this->a->id, ['hazards.view', 'escalations.view', 'escalations.manage']);
        $this->auth($this->owner)->patchJson('/admin/admins/'.$staff->id.'/permissions', ['permissions' => ['escalations.manage', 'escalations.view']])->assertOk();
        $this->assertSame([], $staff->fresh()->permissions);
    }

    public function test_revocation_is_immediate_and_session_version_invalidates_file_sessions(): void
    {
        $staff = $this->user('reader', 'school_staff', $this->a->id, ['users.view']);
        $this->auth($staff)->get('/school/users')->assertOk();
        $staff->forceFill(['permissions' => []])->save();
        $this->getJson('/school/users')->assertForbidden();
        $this->auth($this->owner)->postJson('/admin/admins/'.$staff->id.'/revoke-sessions')->assertOk();
        $old = $staff->session_version;
        $this->actingAs($staff)->withSession(['admin.session_version' => $old])->getJson('/admin')->assertUnauthorized();
    }

    public function test_feature_off_hides_ui_and_denies_legacy_endpoints_without_breaking_map(): void
    {
        $student = $this->user('reporter', 'student', $this->a->id);
        app(SchoolProvisioningService::class)->features($this->owner, $this->a, ['reporting' => false, 'verification' => false]);
        $this->auth($student)->getJson('/hazards/create')->assertForbidden()->assertJsonPath('error.code', 'FEATURE_DISABLED');
        $this->get('/map?school='.$this->a->id)->assertOk();
        $this->auth($this->admin)->getJson('/verifier/queue')->assertForbidden()->assertJsonPath('error.code', 'FEATURE_DISABLED');
        $this->get('/admin')->assertOk()->assertDontSee('Antrean Verifikasi');
    }

    public function test_pending_invite_once_expiry_contact_delivery_and_no_token_in_audit(): void
    {
        $accounts = app(AdminAccountService::class);
        $u = $accounts->create($this->owner, $this->payload('invited'));
        $result = $accounts->issue($this->owner, $u, false, true);
        Mail::assertSent(AdminInvitationMail::class, fn ($m) => $m->hasTo('invited@contact.invalid') && ! $m->hasTo($u->email));
        $parts = parse_url($result['url']);
        $token = basename($parts['path']);
        $this->assertStringNotContainsString($token, json_encode(AuditLog::get()->toArray()));
        $this->get($result['url'])->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post('/invite/'.$token, ['email' => $u->email, 'password' => 'NewAdminPassword456!', 'password_confirmation' => 'NewAdminPassword456!'])->assertRedirect(route('admin.index'));
        $this->assertSame('active', $u->fresh()->status);
        $this->get($result['url'])->assertOk()->assertSee('Tautan tidak berlaku');
        $this->postJson('/invite/'.$token, ['email' => $u->email, 'password' => 'NewAdminPassword456!', 'password_confirmation' => 'NewAdminPassword456!'])->assertUnprocessable()->assertJsonPath('error.code', 'INVITE_INVALID');
    }

    public function test_expired_invite_and_second_issue_invalidate_old_token(): void
    {
        $accounts = app(AdminAccountService::class);
        $u = $accounts->create($this->owner, $this->payload('expires'));
        $first = $accounts->issue($this->owner, $u, false, true);
        $token = basename(parse_url($first['url'], PHP_URL_PATH));
        $second = $accounts->issue($this->owner, $u, false, true);
        $this->assertFalse(Password::broker('invites')->tokenExists($u, $token));
        DB::table('password_reset_tokens')->where('email', $u->email)->update(['created_at' => now()->subHours(49)]);
        $this->get($second['url'])->assertOk()->assertSee('Tautan tidak berlaku');
    }

    public function test_alias_reserved_unique_and_profile_mass_assignment_cannot_grant(): void
    {
        $this->auth($this->owner)->postJson('/admin/admins', [...$this->payload(), 'email' => 'school.admin@ruteaman.my.id'])->assertUnprocessable();
        $this->postJson('/admin/admins', [...$this->payload(), 'email' => 'chief@ruteaman.my.id'])->assertUnprocessable();
        $this->postJson('/admin/admins', [...$this->payload(), 'email' => 'valid@wrong.invalid'])->assertUnprocessable();
        $student = $this->user('guarded', 'student', $this->a->id);
        $this->auth($student)->patchJson('/account/profile', ['display_name' => 'Nama Baru', 'role' => 'owner', 'permissions' => ['users.manage_admins'], 'status' => 'active', 'school_scope' => ['*']])->assertOk();
        $this->assertSame(Role::Student, $student->fresh()->role);
        $this->assertSame([], $student->fresh()->permissions);
        $student->fill(['role' => 'owner', 'permissions' => ['audit.view'], 'status' => 'suspended']);
        $this->assertSame(Role::Student, $student->role);
    }

    public function test_school_provisioning_transaction_valid_geometry_and_no_new_tables(): void
    {
        $before = Schema::getTableListing();
        $geo = ['type' => 'Polygon', 'coordinates' => [[[106.81, -6.18], [106.83, -6.18], [106.83, -6.16], [106.81, -6.16], [106.81, -6.18]]]];
        $data = [...$this->payload('wizard', 'school_admin'), 'name' => 'Sekolah Wizard', 'public_slug' => 'school-wizard', 'latitude' => -6.17, 'longitude' => 106.82, 'bmkg_area_code' => 'TEST', 'timezone' => 'Asia/Jakarta', 'catchment' => json_encode($geo), 'features' => config('ruteaman_admin.features')];
        $this->auth($this->owner)->postJson('/admin/schools', $data)->assertOk();
        $s = School::where('public_slug', 'school-wizard')->firstOrFail();
        $this->assertSame('pending', $s->users()->firstOrFail()->status);
        $this->assertCount(count($before), Schema::getTableListing());
        $data['public_slug'] = 'bad-wizard';
        $data['email'] = 'another.pengelola@ruteaman.my.id';
        $data['longitude'] = 110;
        $this->postJson('/admin/schools', $data)->assertUnprocessable();
        $this->assertDatabaseMissing('schools', ['public_slug' => 'bad-wizard']);
    }

    public function test_scope_bulk_toggle_and_paused_school_read_only(): void
    {
        $this->auth($this->platform)->postJson('/admin/features/apply-all', ['features' => ['trend' => true]])->assertForbidden();
        $this->auth($this->owner)->postJson('/admin/schools/'.$this->a->id.'/pause')->assertOk();
        $this->auth($this->admin)->patchJson('/admin/schools/'.$this->a->id, ['name' => 'Changed'])->assertForbidden()->assertJsonPath('error.code', 'FEATURE_DISABLED');
        $this->get('/school/dashboard')->assertOk()->assertSee('sedang dijeda');
        $this->get('/map?school='.$this->a->id)->assertOk()->assertSee('sedang dijeda');
        $this->auth($this->owner)->postJson('/admin/schools/'.$this->a->id.'/archive')->assertOk();
        $this->get('/api/schools')->assertDontSee('Admin School a');
    }

    public function test_audit_read_only_scoped_and_csv_available_only_with_export_permission(): void
    {
        $this->auth($this->owner)->patchJson('/admin/schools/'.$this->b->id.'/features', ['features' => ['reporting' => false]])->assertOk();
        $this->auth($this->admin)->get('/admin/audit')->assertOk()->assertDontSee('school.feature_toggled');
        $this->get('/admin/audit?csv=1')->assertOk();
        $this->admin->forceFill(['permissions' => ['audit.view']])->save();
        $this->getJson('/admin/audit?csv=1')->assertForbidden();
        $log = AuditLog::firstOrFail();
        $this->expectException(\LogicException::class);
        $log->update(['action' => 'tampered']);
    }

    public function test_console_ui_routes_render_and_switches_have_semantics(): void
    {
        $this->auth($this->owner);
        foreach (['/admin', '/admin/admins', '/admin/admins/create', '/admin/schools', '/admin/schools/create', '/admin/schools/'.$this->a->id, '/admin/features', '/admin/roles', '/admin/audit', '/admin/reauth', '/admin/admins/'.$this->admin->id, '/admin/hazards?school='.$this->a->id] as $url) {
            $this->get($url)->assertOk();
        }$this->get('/admin/features')->assertSee('role="switch"', false);
        $this->get('/admin/schools/create')->assertSee('data-school-wizard', false);
    }

    public function test_optional_feed_metrics_and_export_are_real_permission_gated_endpoints(): void
    {
        $this->getJson('/api/integrations/hazards.geojson?school='.$this->a->id)->assertForbidden()->assertJsonPath('error.code', 'FEATURE_DISABLED');
        $this->get('/parent/'.$this->a->public_slug)->assertNotFound();
        app(SchoolProvisioningService::class)->features($this->owner, $this->a, ['geojson_feed' => true, 'trend' => true]);
        $this->getJson('/api/integrations/hazards.geojson?school='.$this->a->id)->assertOk()->assertJsonPath('type', 'FeatureCollection');
        $this->get('/parent/'.$this->a->public_slug)->assertNotFound();
        $this->auth($this->admin)->get('/admin/schools/'.$this->a->id.'/metrics')->assertOk();
        $this->get('/admin/schools/'.$this->a->id.'/export')->assertOk();
        $this->get('/admin/schools/'.$this->a->id.'/feed-settings')->assertOk();
        $this->patch('/admin/schools/'.$this->a->id.'/feed-settings', ['public_escalation_status' => true])->assertRedirect();
        $this->auth($this->platform)->getJson('/admin/schools/'.$this->b->id.'/export')->assertForbidden();
    }

    public function test_geometry_self_intersection_and_missing_coordinates_are_rejected_without_500(): void
    {
        $data = ['catchment' => json_encode(['type' => 'Polygon', 'coordinates' => [[[106.81, -6.18], [106.83, -6.16], [106.83, -6.18], [106.81, -6.16], [106.81, -6.18]]]]), 'longitude' => 106.82, 'latitude' => -6.17];
        $this->auth($this->owner)->patchJson('/admin/schools/'.$this->a->id, $data)->assertUnprocessable();
        $this->patchJson('/admin/schools/'.$this->a->id, ['catchment' => $data['catchment']])->assertUnprocessable();
    }

    public function test_admin_idle_expiry_and_deletion_audit_model_guard(): void
    {
        $this->auth($this->admin)->withSession(['admin.last_seen' => time() - 21 * 60])->getJson('/admin')->assertUnauthorized();
        $log = AuditLog::create(['actor_id' => $this->owner->id, 'action' => 'test.fixture', 'entity' => 'user', 'entity_id' => $this->owner->id, 'created_at' => now()]);
        $this->expectException(\LogicException::class);
        $log->delete();
    }

    public function test_reset_link_rejects_suspended_account_and_revoke_on_accept(): void
    {
        $u = $this->user('reset-target', 'school_staff', $this->a->id, ['users.view']);
        $this->auth($this->owner)->postJson('/admin/admins/'.$u->id.'/reset-link')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $u->forceFill(['status' => 'suspended', 'active' => false])->save();
        $this->postJson('/admin/admins/'.$u->id.'/reset-link')->assertUnprocessable();
    }

    public function test_permission_editor_preserves_catalog_keys_and_alias_placeholder_is_rendered(): void
    {
        $this->auth($this->owner)->get('/admin/admins/create')->assertOk()->assertSee('value="dashboard.view"', false)->assertSee('data-permission="users.manage_admins"', false)->assertSee('sekolah.pengelola@ruteaman.my.id')->assertDontSee("{{ config('ruteaman_admin.alias_domain') }}", false);
    }

    public function test_hazard_reopen_supersedes_rejection_and_platform_can_manage_school_students(): void
    {
        $this->seed(DemoSeeder::class);
        $h = Hazard::where('public_slug', 'demo-lubang-jl-mawar')->firstOrFail();
        $h->update(['status' => 'resolved', 'resolved_at' => now()]);
        Verification::create(['hazard_id' => $h->id, 'verifier_id' => $this->owner->id, 'decision' => 'rejected', 'created_at' => now()]);
        $this->auth($this->owner)->postJson(route('admin.hazards.reopen', $h))->assertOk();
        $this->assertSame('disputed', $h->fresh()->status->value);
        $this->assertSame('needs_evidence', $h->verifications()->latest('id')->value('decision'));
        $this->get(route('conditions.show', $h))->assertOk();
        $this->postJson(route('admin.hazards.reopen', $h))->assertUnprocessable();
        $this->get('/school/users?school='.$this->a->id)->assertOk();
        $this->auth($this->platform)->get('/school/users?school='.$this->b->id)->assertForbidden();
    }

    public function test_archived_school_is_filtered_from_national_directory_and_rejected_on_registration(): void
    {
        $this->a->forceFill(['npsn' => '30303030', 'status' => 'archived'])->save();
        $directory = $this->mock(DapodikSchoolDirectory::class);
        $directory->shouldReceive('search')->once()->andReturn([['npsn' => '30303030', 'name' => 'Archived school'], ['npsn' => '40404040', 'name' => 'Available school']]);
        $this->getJson('/api/school-directory/schools?query=school&province_code=31&regency_code=31.71&district_code=31.71.01')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.npsn', '40404040');
        $directory->shouldReceive('import')->once()->andReturn($this->a);
        $this->postJson('/register', ['display_name' => 'New Student', 'province_code' => '31', 'regency_code' => '31.71', 'district_code' => '31.71.01', 'school_npsn' => '30303030', 'email' => 'archived-register@example.test', 'password' => 'StudentPassword123!', 'password_confirmation' => 'StudentPassword123!', 'parental_consent' => true])->assertUnprocessable()->assertJsonValidationErrors('school_npsn');
        $this->assertDatabaseMissing('users', ['email' => 'archived-register@example.test']);
    }

    public function test_parent_view_is_absent_from_admin_ui_and_old_feature_submissions_are_rejected(): void
    {
        $this->a->forceFill(['features' => [...config('ruteaman_admin.features'), 'parent_view' => true]])->save();
        $this->auth($this->owner)->get('/admin/features')->assertOk()->assertDontSee('Tampilan orang tua')->assertDontSee('name="features[parent_view]"', false);
        $this->get('/admin/admins/'.$this->admin->id)->assertOk()->assertSee('Atur feed GeoJSON')->assertDontSee('tampilan orang tua')->assertDontSee('Fitur Tampilan orang tua');
        $this->get('/admin/schools/'.$this->a->id)->assertOk()->assertDontSee('Feed dan Orang Tua');
        $this->patchJson('/admin/schools/'.$this->a->id.'/features', ['features' => ['parent_view' => true]])->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_FAILED')->assertJsonStructure(['error' => ['field_errors' => ['features']]]);
        $this->postJson('/admin/features/apply-all', ['features' => ['parent_view' => true]])->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_FAILED')->assertJsonStructure(['error' => ['field_errors' => ['features']]]);
        $this->a->forceFill(['features' => [...config('ruteaman_admin.features'), 'geojson_feed' => true]])->save();
        $this->auth($this->admin)->get('/admin/schools/'.$this->a->id.'/feed-settings')->assertOk()->assertSee('Feed GeoJSON')->assertDontSee('parent/')->assertDontSee('orang tua');
    }

    public function test_parent_flag_cleanup_preserves_other_features_on_spatial_database(): void
    {
        $features = [...config('ruteaman_admin.features'), 'parent_view' => true, 'geojson_feed' => true, 'require_guest_review' => true, 'require_account_review' => false];
        $this->a->forceFill(['features' => $features])->save();
        $auditCount = AuditLog::count();
        $migration = require database_path('migrations/2026_10_18_000000_retire_parent_view_feature.php');
        $migration->up();
        unset($features['parent_view']);
        $this->assertSame($features, $this->a->fresh()->features);
        $snapshot = $this->a->fresh()->features;
        $migration->up();
        $this->assertSame($snapshot, $this->a->fresh()->features);
        $this->assertSame($auditCount, AuditLog::count());
    }
}
