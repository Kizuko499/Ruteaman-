<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\QuickUserDeletion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuickUserDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'mail.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Cache::flush();
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
            foreach (['display_name', 'email', 'password', 'role', 'status'] as $c) {
                $t->string($c);
            }$t->boolean('active')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->integer('session_version')->default(0);
            $t->unsignedBigInteger('school_id')->nullable();
            foreach (['contact_email', 'permissions', 'school_scope', 'remember_token', 'pending_email', 'email_verification_token', 'email_verification_expires_at', 'avatar_path', 'avatar_preset', 'parental_consent_at', 'email_verified_at', 'notif_seen_at'] as $c) {
                $t->text($c)->nullable();
            }
        });
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('status')->default('active');
            $t->text('features')->nullable();
        });
        DB::table('schools')->insert(['id' => 1, 'name' => 'Fixture school']);
        Schema::create('admin_transaction_locks', fn (Blueprint $t) => $t->id());
        DB::table('admin_transaction_locks')->insert(['id' => 1]);
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email');
            $t->string('token')->nullable();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            foreach (['actor_id', 'entity_id', 'school_id', 'target_id'] as $c) {
                $t->unsignedBigInteger($c)->nullable();
            }foreach (['action', 'entity', 'meta_json', 'created_at', 'occurred_at', 'actor_name', 'actor_email', 'actor_role', 'category', 'severity', 'result', 'target_type', 'target_label', 'changes', 'meta', 'reason', 'ip_address', 'user_agent', 'request_id', 'route_name', 'http_method'] as $c) {
                $t->text($c)->nullable();
            }
        });
        foreach (['hazard_reports', 'hazard_confirmations'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('reporter_id');
            });
        }
        foreach ([[1, 'owner', null], [2, 'student', 1], [3, 'school_admin', 1]] as [$id,$role,$school]) {
            DB::table('users')->insert(['id' => $id, 'display_name' => 'Fixture '.$id, 'email' => 'fixture'.$id.'@example.invalid', 'password' => Hash::make('SyntheticSessionOwner928!'), 'role' => $role, 'status' => 'active', 'school_id' => $school]);
        }
        $this->loginAs(1);
    }

    private function loginAs(int $id): void
    {
        $u = User::findOrFail($id);
        $this->actingAs($u)->withSession(['admin.session_version' => 0, 'admin.last_seen' => time(), 'admin.reauth_at' => 0]);
    }

    private function ticket(int $target = 2): string
    {
        $request = Request::create('/admin/users/'.$target);
        $request->setUserResolver(fn () => User::findOrFail(1));
        $request->setLaravelSession(app('session.store'));
        $token = app(QuickUserDeletion::class)->issue($request, User::findOrFail($target));
        $this->withSession(app('session.store')->all());

        return $token;
    }

    public function test_active_owner_can_confirm_without_name_password_or_fresh_reauth(): void
    {
        $token = $this->ticket();
        DB::table('hazard_reports')->insert(['id' => 1, 'reporter_id' => 2]);
        DB::table('hazard_confirmations')->insert(['id' => 1, 'reporter_id' => 2]);
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1, 'permanent' => 1])->assertOk();
        $u = User::findOrFail(2);
        $this->assertFalse($u->active);
        $this->assertSame('Pengguna dihapus', $u->display_name);
        $this->assertSame('suspended', $u->status);
        $this->assertGreaterThan(0, $u->session_version);
        $this->assertDatabaseHas('hazard_reports', ['reporter_id' => 2]);
        $this->assertDatabaseHas('hazard_confirmations', ['reporter_id' => 2]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.anonymized', 'actor_id' => 1, 'entity_id' => 2]);
        $this->assertStringNotContainsString($token, DB::table('audit_logs')->get()->toJson());
    }

    public function test_missing_explicit_continue_is_rejected(): void
    {
        $token = $this->ticket();
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token])->assertUnprocessable();
        $this->assertTrue(User::find(2)->active);
    }

    public function test_missing_or_forged_token_is_rejected(): void
    {
        $this->ticket();
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => 'Forged', 'confirmed' => 1])->assertUnprocessable();
        $this->assertTrue(User::find(2)->active);
    }

    public function test_token_cannot_target_a_different_account(): void
    {
        $token = $this->ticket();
        $this->deleteJson('/admin/users/3/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertUnprocessable();
        $this->assertTrue(User::find(3)->active);
    }

    public function test_replay_is_rejected(): void
    {
        $token = $this->ticket();
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertOk();
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertUnprocessable();
    }

    public function test_token_is_bound_to_current_session(): void
    {
        $token = $this->ticket();
        $this->withSession(['owner_delete_confirmations' => []]);
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertUnprocessable();
        $this->assertTrue(User::find(2)->active);
    }

    public function test_changed_target_identity_invalidates_confirmation(): void
    {
        $token = $this->ticket();
        DB::table('users')->where('id', 2)->update(['display_name' => 'Changed identity']);
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertUnprocessable();
        $this->assertTrue(User::find(2)->active);
    }

    public function test_non_owner_cannot_use_quick_delete(): void
    {
        $token = $this->ticket();
        $this->loginAs(3);
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertForbidden();
        $this->assertTrue(User::find(2)->active);
    }

    public function test_self_and_last_owner_are_still_protected(): void
    {
        $token = $this->ticket(1);
        $this->deleteJson('/admin/users/1/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertForbidden();
        $this->assertTrue(User::find(1)->active);
    }

    public function test_legacy_delete_endpoint_still_requires_reauth(): void
    {
        $this->deleteJson('/admin/users/2', ['confirmation' => 'Fixture 2', 'delete_password' => 'SyntheticSessionOwner928!'])->assertStatus(428);
        $this->assertTrue(User::find(2)->active);
    }

    public function test_page_token_is_not_flashed_after_validation_error(): void
    {
        $token = $this->ticket();
        $this->delete('/admin/users/2/confirm-delete', ['delete_token' => $token])->assertSessionHasErrors('confirmed');
        $this->assertNull(session('_old_input.delete_token'));
    }

    public function test_expired_page_confirmation_is_rejected(): void
    {
        $token = $this->ticket();
        $entries = app('session.store')->get('owner_delete_confirmations');
        $entries[2]['expires'] = time() - 1;
        $this->withSession(['owner_delete_confirmations' => $entries]);
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertUnprocessable();
        $this->assertTrue(User::find(2)->active);
    }

    public function test_revoked_session_cannot_delete(): void
    {
        $token = $this->ticket();
        $this->withSession(['admin.session_version' => 99]);
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertStatus(401);
        $this->assertTrue(User::find(2)->active);
    }

    public function test_logged_out_request_cannot_delete(): void
    {
        $token = $this->ticket();
        auth()->logout();
        $this->deleteJson('/admin/users/2/confirm-delete', ['delete_token' => $token, 'confirmed' => 1])->assertUnauthorized();
        $this->assertTrue(User::find(2)->active);
    }
}
