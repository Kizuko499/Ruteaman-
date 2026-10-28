<?php

namespace Tests\Feature;

use App\Http\Controllers\AuditLogController;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\AdminPasswordPolicy;
use App\Services\AuditLogger;
use App\Services\PermissionService;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminRepairTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    private School $school;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Dedicated spatial DB required.');
        }parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('r', 32)), 'cache.default' => 'array', 'mail.default' => 'smtp']);
        Cache::flush();
        Mail::fake();
        $this->seed(DemoSeeder::class);
        $this->school = School::firstOrFail();
        $this->owner = $this->user('OwnerRepair', 'owner', null);
        $this->auth($this->owner);
    }

    private function user(string $name, string $role = 'school_admin', ?int $school = null): User
    {
        $u = (new User)->forceFill(['display_name' => $name, 'email' => strtolower($name).'@ruteaman.my.id', 'contact_email' => strtolower($name).'@contact.invalid', 'password' => 'FixtureAdminPassword123!', 'role' => $role, 'school_id' => $role === 'owner' ? null : ($school ?? $this->school->id), 'permissions' => $role === 'owner' ? [] : config('ruteaman_roles.ceilings.'.$role, []), 'school_scope' => $role === 'platform_admin' ? ['*'] : null, 'status' => 'active', 'active' => true]);
        $u->save();

        return $u;
    }

    private function auth(User $u, bool $reauth = true)
    {
        return $this->actingAs($u)->withSession(['admin.session_version' => $u->session_version, 'admin.last_seen' => time(), 'admin.reauth_at' => $reauth ? time() : 0]);
    }

    private function data(string $role = 'school_admin'): array
    {
        return ['display_name' => 'NewPerson', 'email' => 'new.person@ruteaman.my.id', 'contact_email' => 'new@contact.invalid', 'role' => $role, 'school_id' => $role === 'owner' ? null : $this->school->id, 'permissions' => [], 'creation_method' => 'password_set', 'initial_password' => 'SyntheticSecret482!Qz', 'initial_password_confirmation' => 'SyntheticSecret482!Qz'];
    }

    public function test_primary_key_and_indexes_verified_in_actual_database(): void
    {
        $indexes = Schema::getIndexes('audit_logs');
        $pk = collect($indexes)->first(fn ($i) => $i['primary']);
        $this->assertSame(['id'], $pk['columns']);
        foreach (['audit_actor_created', 'audit_action_created', 'audit_school_created', 'audit_occurred_id'] as $n) {
            $this->assertContains($n, array_column($indexes, 'name'));
        }$l = app(AuditLogger::class)->record($this->owner, 'test.created', 'user', $this->owner->id);
        $this->expectException(QueryException::class);
        DB::table('audit_logs')->insert(['id' => $l->id, 'action' => 'duplicate', 'entity' => 'user', 'created_at' => now()]);
    }

    public function test_database_and_model_reject_audit_mutation(): void
    {
        $l = app(AuditLogger::class)->record($this->owner, 'test.created', 'user', $this->owner->id);
        try {
            AuditLog::whereKey($l->id)->delete();
            $this->fail('Model delete allowed');
        } catch (\LogicException) {
        }try {
            DB::transaction(fn () => DB::table('audit_logs')->where('id', $l->id)->update(['action' => 'tamper']));
            $this->fail('DB update allowed');
        } catch (QueryException) {
        }$this->assertDatabaseHas('audit_logs', ['id' => $l->id, 'action' => 'test.created']);
    }

    public function test_two_active_tiers_and_read_only_legacy_report(): void
    {
        $legacy = $this->user('LegacyRepair', 'platform_admin');
        $before = $legacy->fresh()->toArray();
        $this->get('/admin/roles')->assertOk()->assertDontSee('Admin Platform')->assertDontSee('Staf Admin Sekolah');
        $this->get('/admin/users/create')->assertOk()->assertDontSee('value="platform_admin"', false)->assertDontSee('value="school_staff"', false);
        $this->get('/admin/users')->assertOk()->assertSee('Tingkat lama (nonaktif)');
        $this->artisan('ruteaman:admin:report-legacy-tiers')->assertSuccessful();
        $this->assertEquals($before, $legacy->fresh()->toArray());
        $this->postJson('/admin/users', $this->data('platform_admin'))->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_FAILED');
        $this->assertNotContains('users.manage_admins', config('ruteaman_roles.ceilings.school_admin'));
    }

    public function test_legacy_effective_permissions_are_not_silently_revoked(): void
    {
        $u = $this->user('Grandfathered', 'platform_admin');
        $before = app(PermissionService::class)->granted($u);
        $this->artisan('ruteaman:admin:report-legacy-tiers')->assertSuccessful();
        $this->assertSame($before, app(PermissionService::class)->granted($u->fresh()));
        $this->assertContains('schools.provision', $before);
    }

    public function test_school_admin_cannot_access_all_users_or_create_admins(): void
    {
        $u = $this->user('PrincipalRepair');
        $this->auth($u)->getJson('/admin/users')->assertForbidden();
        $this->postJson('/admin/users', $this->data())->assertForbidden();
        $this->postJson('/admin/admins', $this->data())->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $u->id, 'action' => 'permission.denied', 'result' => 'denied']);
    }

    public function test_initial_password_hash_and_redaction_and_forced_change(): void
    {
        $d = $this->data();
        $this->postJson('/admin/users', $d)->assertOk()->assertDontSee($d['initial_password']);
        $u = User::where('email', $d['email'])->firstOrFail();
        $this->assertTrue(Hash::check($d['initial_password'], $u->password));
        $this->assertTrue($u->must_change_password);
        $this->assertSame('active', $u->status);
        $this->assertStringNotContainsString($d['initial_password'], AuditLog::all()->toJson());
        $this->auth($u)->get('/admin')->assertRedirect(route('password.force.show'));
        $this->get('/password/required')->assertOk();
        $this->post('/password/required', ['current_password' => $d['initial_password'], 'password' => 'DifferentPrivate679!XY', 'password_confirmation' => 'DifferentPrivate679!XY'])->assertRedirect(route('admin.index'));
        $this->assertFalse($u->fresh()->must_change_password);
        $this->get('/admin')->assertOk();
    }

    public function test_password_policy_and_sensitive_input_is_not_flashed(): void
    {
        $d = $this->data();
        $d['initial_password'] = $d['initial_password_confirmation'] = 'alllowercasepassword';
        $this->post('/admin/users', $d)->assertSessionHasErrors('initial_password');
        $this->assertNull(session('_old_input.initial_password'));
        $this->assertNull(session('_old_input.initial_password_confirmation'));
    }

    public function test_generator_requires_reauth_and_does_not_persist_password(): void
    {
        $this->auth($this->owner, false)->postJson('/admin/users/generate-password')->assertStatus(428);
        $this->auth($this->owner);
        $d = $this->postJson('/admin/users/generate-password')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json();
        $this->assertSame(20, strlen($d['password']));
        app(AdminPasswordPolicy::class)->validate($d['password'], '', '');
        $this->assertStringNotContainsString($d['password'], AuditLog::all()->toJson());
    }

    public function test_owner_can_edit_profiles_of_all_roles_and_another_owner(): void
    {
        $other = $this->user('SecondRepairOwner', 'owner');
        $this->patchJson('/admin/users/'.$other->id, ['display_name' => 'Second Updated', 'email' => $other->email, 'contact_email' => $other->contact_email])->assertOk();
        $this->assertSame('Second Updated', $other->fresh()->display_name);
        $student = $this->user('StudentRepair', 'student');
        $this->patchJson('/admin/users/'.$student->id, ['display_name' => 'Updated Learner', 'email' => $student->email, 'contact_email' => null, 'school_id' => $this->school->id])->assertOk();
        $this->assertSame('Updated Learner', $student->fresh()->display_name);
    }

    public function test_permission_diff_has_actor_snapshot_and_context(): void
    {
        $u = $this->user('TargetRepair');
        $this->withHeader('User-Agent', 'Repair QA browser')->patchJson('/admin/users/'.$u->id.'/permissions', ['permissions' => ['audit.view'], 'reason' => 'Tugas berubah'])->assertOk();
        $l = AuditLog::where('action', 'admin.permissions_changed')->latest('id')->firstOrFail();
        $this->assertSame($this->owner->display_name, $l->actor_name);
        $this->assertSame($this->owner->email, $l->actor_email);
        $this->assertSame('owner', $l->actor_role);
        $this->assertSame('TargetRepair', $l->target_label);
        $this->assertSame('success', $l->result);
        $this->assertSame('Repair QA browser', $l->user_agent);
        $this->assertNotEmpty($l->request_id);
        $this->assertSame('admin.users.permissions', $l->route_name);
        $this->assertNotEmpty($l->changes['permissions']);
    }

    public function test_recursive_secret_redaction(): void
    {
        $l = app(AuditLogger::class)->record($this->owner, 'test.redaction', 'user', $this->owner->id, ['password' => 'SECRET_CANARY', 'nested' => ['reset_url' => 'https://example.invalid/reset/TOKEN_CANARY', 'origin_latitude' => -6.7]], null, ['password' => ['before' => 'OLD_CANARY', 'after' => 'NEW_CANARY']]);
        $text = $l->toJson();
        foreach (['SECRET_CANARY', 'TOKEN_CANARY', 'OLD_CANARY', 'NEW_CANARY', '-6.7'] as $secret) {
            $this->assertStringNotContainsString($secret, $text);
        }
    }

    public function test_self_access_and_last_owner_are_protected(): void
    {
        User::where('role', 'owner')->whereKeyNot($this->owner->id)->update(['active' => false, 'status' => 'suspended']);
        $this->patchJson('/admin/users/'.$this->owner->id.'/permissions', ['permissions' => []])->assertForbidden();
        $this->postJson('/admin/users/'.$this->owner->id.'/suspend')->assertForbidden()->assertJsonPath('error.code', 'LAST_OWNER');
        $this->deleteJson('/admin/users/'.$this->owner->id, ['confirmation' => $this->owner->display_name, 'delete_password' => 'FixtureAdminPassword123!'])->assertForbidden();
    }

    public function test_password_reset_revokes_sessions_and_does_not_expose_secret(): void
    {
        $u = $this->user('PwTargetRepair');
        $old = $u->session_version;
        $d = ['initial_password' => 'DifferentPrivate679!XY', 'initial_password_confirmation' => 'DifferentPrivate679!XY'];
        $this->putJson('/admin/users/'.$u->id.'/password', $d)->assertOk()->assertDontSee($d['initial_password']);
        $this->assertGreaterThan($old, $u->fresh()->session_version);
        $this->assertTrue($u->fresh()->must_change_password);
    }

    public function test_anonymization_preserves_user_id_and_audit_snapshot(): void
    {
        $u = $this->user('EraseTargetRepair', 'student');
        $old = app(AuditLogger::class)->record($u, 'auth.login', 'user', $u->id);
        $this->deleteJson('/admin/users/'.$u->id, ['confirmation' => $u->email, 'delete_password' => 'FixtureAdminPassword123!'])->assertOk();
        $this->assertSame('Pengguna dihapus', $u->fresh()->display_name);
        $this->assertSame('EraseTargetRepair', $old->fresh()->actor_name);
        $this->assertFalse($u->fresh()->active);
    }

    public function test_permanent_delete_only_pending_without_evidence(): void
    {
        $u = $this->user('EmptyTargetRepair', 'student');
        $u->forceFill(['status' => 'pending', 'active' => false])->save();
        $this->deleteJson('/admin/users/'.$u->id, ['confirmation' => $u->email, 'delete_password' => 'FixtureAdminPassword123!', 'permanent' => true])->assertOk();
        $this->assertNull(User::find($u->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.permanently_deleted', 'target_id' => $u->id]);
    }

    public function test_scoped_audit_filter_export_and_csv_injection(): void
    {
        $u = $this->user('ScopedRepair');
        $other = School::whereKeyNot($this->school->id)->firstOrFail();
        app(AuditLogger::class)->record($this->owner, 'test.school-b', 'school', $other->id, [], $other->id);
        $this->auth($u)->get('/admin/audit')->assertOk()->assertDontSee('test.school-b');
        $this->getJson('/admin/audit?school_id='.$other->id)->assertForbidden();
        $this->auth($this->owner);
        $this->getJson('/admin/audit?csv=1&from=2020-01-01&until=2026-12-31')->assertUnprocessable();
        $this->assertSame("'=SUM(A1:A2)", AuditLogController::csvCell('=SUM(A1:A2)'));
        $this->assertSame("'  @evil", AuditLogController::csvCell('  @evil'));
        $this->get('/admin/audit?csv=1')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'data.audit_exported']);
    }

    public function test_keyset_cursor_is_stable_when_new_rows_arrive(): void
    {
        for ($i = 0; $i < 35; $i++) {
            app(AuditLogger::class)->record($this->owner, 'test.page', 'user', $this->owner->id);
        }$first = $this->get('/admin/audit?action=test.page')->assertOk();
        $logs = $first->viewData('logs');
        $cursor = $first->viewData('nextCursor');
        $this->assertCount(30, $logs);
        $new = app(AuditLogger::class)->record($this->owner, 'test.page', 'user', $this->owner->id);
        $second = $this->get('/admin/audit?action=test.page&cursor='.urlencode($cursor))->assertOk()->viewData('logs');
        $this->assertCount(5, $second);
        $this->assertEmpty(array_intersect($logs->pluck('id')->all(), $second->pluck('id')->all()));
        $this->assertNotContains($new->id, $second->pluck('id')->all());
    }

    public function test_login_failure_is_an_unverified_attempt_not_the_account_actor(): void
    {
        auth()->logout();
        $this->post('/login', ['email' => $this->owner->email, 'password' => 'WrongSyntheticPassword123!'])->assertRedirect();
        $l = AuditLog::where('action', 'auth.login_failed')->latest('id')->firstOrFail();
        $this->assertNull($l->actor_id);
        $this->assertSame($this->owner->id, $l->target_id);
        $this->assertSame('failed', $l->result);
        $this->assertNotEmpty($l->route_name);
    }

    public function test_nonadmin_creation_and_invitation_remain_available(): void
    {
        $d = $this->data('verifier');
        $d['email'] = 'verifier.real@example.test';
        $d['creation_method'] = 'invite';
        unset($d['initial_password'],$d['initial_password_confirmation']);
        $res = $this->postJson('/admin/users', $d)->assertOk();
        $u = User::where('email', $d['email'])->firstOrFail();
        $this->assertSame('pending', $u->status);
        $url = $res->json('invite.url');
        $token = basename(parse_url($url, PHP_URL_PATH));
        auth()->logout();
        $this->post('/invite/'.$token, ['email' => $u->email, 'password' => 'DifferentPrivate679!XY', 'password_confirmation' => 'DifferentPrivate679!XY'])->assertRedirect(route('map'));
        $this->assertTrue($u->fresh()->active);
    }

    public function test_raw_database_delete_is_blocked(): void
    {
        $l = app(AuditLogger::class)->record($this->owner, 'test.immutable', 'user', $this->owner->id);
        try {
            DB::transaction(fn () => DB::table('audit_logs')->where('id', $l->id)->delete());
            $this->fail('SQL deletion allowed');
        } catch (QueryException) {
        }$this->assertDatabaseHas('audit_logs', ['id' => $l->id]);
    }

    public function test_sensitive_owner_mutations_require_reauth_and_delete_confirmation(): void
    {
        $u = $this->user('ProtectedRepair', 'student');
        $this->auth($this->owner, false)->postJson('/admin/users/'.$u->id.'/suspend')->assertStatus(428);
        $this->auth($this->owner)->deleteJson('/admin/users/'.$u->id, ['confirmation' => 'Wrong alias', 'delete_password' => 'FixtureAdminPassword123!'])->assertUnprocessable();
        $this->assertTrue($u->fresh()->active);
    }

    public function test_old_entries_are_not_backfilled_with_guessed_identity(): void
    {
        $l = AuditLog::create(['actor_id' => $this->owner->id, 'action' => 'test.legacy', 'entity' => 'user', 'entity_id' => $this->owner->id, 'created_at' => now()]);
        $this->get('/admin/audit?action=test.legacy')->assertOk()->assertSee('test.legacy');
        $this->assertNull($l->fresh()->actor_name);
        $this->assertNull($l->fresh()->occurred_at);
    }

    public function test_rendered_server_failure_records_only_safe_context(): void
    {
        Route::get('/admin/qa-failure', fn () => response()->json(['message' => 'Failure fixture'], 503))->middleware(['web', 'auth']);
        $this->getJson('/admin/qa-failure')->assertStatus(503);
        $this->assertDatabaseHas('audit_logs', ['action' => 'system.request_failed', 'result' => 'failed']);
    }
}
