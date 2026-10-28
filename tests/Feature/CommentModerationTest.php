<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommentModerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('display_name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('role')->default('student');
            $t->string('status')->default('active');
            $t->boolean('active')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->unsignedBigInteger('school_id')->nullable();
            $t->text('school_scope')->nullable();
            $t->text('permissions')->nullable();
            $t->unsignedInteger('session_version')->default(1);
            $t->timestamps();
        });
        Schema::create('hazards', function (Blueprint $t) {
            $t->id();
            $t->string('public_slug')->unique();
            $t->string('status')->default('confirmed');
            $t->unsignedBigInteger('type_id')->nullable();
            $t->string('location_label')->nullable();
            $t->string('publication_control')->default('normal');
            $t->boolean('requires_moderation')->default(false);
            $t->timestamp('first_reported_at')->nullable();
            $t->timestamp('last_confirmed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('hazard_comments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('hazard_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->text('body');
            $t->timestamps();
        });
        Schema::create('hazard_types', function (Blueprint $t) {
            $t->id();
            $t->string('key')->nullable();
            $t->string('name')->nullable();
            $t->string('label')->nullable();
            $t->string('slug')->nullable();
            $t->string('icon')->nullable();
            $t->string('color')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('hazard_reports', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('hazard_id')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('reporter_id')->nullable();
            $t->string('status')->nullable();
            $t->string('photo_path')->nullable();
            $t->timestamp('observed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->timestamp('occurred_at')->nullable();
            $t->string('actor_name')->nullable();
            $t->string('actor_email')->nullable();
            $t->string('actor_role')->nullable();
            $t->string('category')->nullable();
            $t->string('severity')->nullable();
            $t->string('http_method')->nullable();
            $t->string('route_name')->nullable();
            $t->string('request_id')->nullable();
            $t->string('target_label')->nullable();
            $t->string('target_type')->nullable();
            $t->unsignedBigInteger('target_id')->nullable();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('entity')->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->string('action');
            $t->text('meta')->nullable();
            $t->text('meta_json')->nullable();
            $t->text('changes')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->string('result')->nullable();
            $t->string('reason')->nullable();
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
        });
        DB::table('hazards')->insert(['id' => 1, 'public_slug' => 'hz-uji', 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('hazard_comments')->insert(['id' => 1, 'hazard_id' => 1, 'user_id' => null, 'body' => 'Komentar tamu untuk diuji.', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function user(string $role, ?array $permissions): User
    {
        $u = new User;
        $u->forceFill(['display_name' => 'Penguji', 'email' => uniqid().'@uji.id', 'password' => 'x', 'role' => Role::from($role), 'status' => 'active', 'active' => true, 'permissions' => $permissions])->save();

        return $u;
    }

    private function login(User $u): void
    {
        // Middleware AdminSession mencocokkan versi sesi dengan kolom user.
        $this->actingAs($u);
        $this->withSession(['admin.session_version' => (int) $u->refresh()->session_version]);
    }

    public function test_permission_registered_with_admin_ceilings(): void
    {
        $registry = config('ruteaman_permissions');
        $this->assertArrayHasKey('comments.delete', $registry);
        $this->assertSame('Kondisi', $registry['comments.delete']['group']);
        $this->assertContains('hazards.view', $registry['comments.delete']['dependencies']);
        $ceilings = config('ruteaman_roles.ceilings');
        $this->assertContains('comments.delete', $ceilings['platform_admin']);
        $this->assertContains('comments.delete', $ceilings['school_admin']);
        $this->assertNotContains('comments.delete', $ceilings['school_staff']);
        $this->assertNotContains('comments.delete', $ceilings['verifier']);
        $this->assertNotContains('comments.delete', $ceilings['student']);
    }

    public function test_guest_cannot_delete(): void
    {
        $this->delete('/kondisi/hz-uji/komentar/1')->assertRedirect();
        $this->assertDatabaseHas('hazard_comments', ['id' => 1]);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $this->login($this->user('school_admin', ['hazards.view']));
        $this->delete('/kondisi/hz-uji/komentar/1')->assertForbidden();
        $this->assertDatabaseHas('hazard_comments', ['id' => 1]);
    }

    public function test_admin_with_permission_deletes_and_audit_written(): void
    {
        $admin = $this->user('school_admin', ['hazards.view', 'comments.delete']);
        $this->login($admin);
        $this->from('/kondisi/hz-uji')->delete('/kondisi/hz-uji/komentar/1')->assertRedirect('/kondisi/hz-uji');
        $this->assertDatabaseMissing('hazard_comments', ['id' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'comment.deleted', 'entity' => 'hazard_comment', 'entity_id' => 1, 'actor_id' => $admin->id]);
    }

    public function test_owner_deletes_without_stored_grant(): void
    {
        $this->login($this->user('owner', null));
        $this->delete('/kondisi/hz-uji/komentar/1')->assertRedirect();
        $this->assertDatabaseMissing('hazard_comments', ['id' => 1]);
    }

    public function test_comment_of_other_hazard_is_404(): void
    {
        DB::table('hazards')->insert(['id' => 2, 'public_slug' => 'hz-lain', 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
        $this->login($this->user('owner', null));
        $this->delete('/kondisi/hz-lain/komentar/1')->assertNotFound();
        $this->assertDatabaseHas('hazard_comments', ['id' => 1]);
    }
}
