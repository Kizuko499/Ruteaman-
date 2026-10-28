<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\DapodikSchoolDirectory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminSchoolPickerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'mail.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('display_name');
            $t->string('email');
            $t->string('password');
            $t->string('role');
            $t->string('status');
            $t->boolean('active')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->integer('session_version')->default(0);
            $t->unsignedBigInteger('school_id')->nullable();
            $t->text('permissions')->nullable();
            $t->text('school_scope')->nullable();
        });
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('npsn')->unique();
            $t->string('status')->default('active');
            foreach (['public_slug', 'province_name', 'regency_name', 'district_name', 'features'] as $name) {
                $t->text($name)->nullable();
            }
            $t->unsignedBigInteger('created_by')->nullable();
        });
        Schema::create('admin_transaction_locks', fn (Blueprint $t) => $t->id());
        DB::table('admin_transaction_locks')->insert(['id' => 1]);
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            foreach (['actor_id', 'entity_id', 'school_id', 'target_id'] as $name) {
                $t->unsignedBigInteger($name)->nullable();
            }
            foreach (['action', 'entity', 'meta_json', 'created_at', 'occurred_at', 'actor_name', 'actor_email', 'actor_role', 'category', 'severity', 'result', 'target_type', 'target_label', 'changes', 'meta', 'reason', 'ip_address', 'user_agent', 'request_id', 'route_name', 'http_method'] as $name) {
                $t->text($name)->nullable();
            }
        });
        DB::table('users')->insert([
            ['id' => 1, 'display_name' => 'OwnerPicker', 'email' => 'owner@fixture.invalid', 'password' => Hash::make('SyntheticPicker928!'), 'role' => 'owner', 'status' => 'active'],
            ['id' => 2, 'display_name' => 'SchoolPicker', 'email' => 'school@fixture.invalid', 'password' => Hash::make('SyntheticPicker928!'), 'role' => 'school_admin', 'status' => 'active'],
        ]);
        DB::table('schools')->insert(['id' => 1, 'name' => 'Registered School', 'npsn' => '20202020']);
        $this->loginAs(1);
    }

    private function loginAs(int $id, bool $reauth = true): void
    {
        $this->actingAs(User::findOrFail($id))->withSession(['admin.session_version' => 0, 'admin.last_seen' => time(), 'admin.reauth_at' => $reauth ? time() : 0]);
    }

    private function directory(): MockInterface
    {
        $mock = Mockery::mock(DapodikSchoolDirectory::class);
        $this->app->instance(DapodikSchoolDirectory::class, $mock);

        return $mock;
    }

    public function test_search_merges_registered_and_national_schools_without_importing(): void
    {
        $this->directory()->shouldReceive('searchNationwide')->once()->with('School')->andReturn([
            ['name' => 'Registered School', 'npsn' => '20202020'],
            ['name' => 'National School', 'npsn' => '10101010', 'province' => 'Province'],
        ]);
        $this->getJson('/admin/school-picker/search?q=School')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.source', 'registered')->assertJsonPath('data.1.source', 'directory');
        $this->assertSame(1, DB::table('schools')->count());
    }

    public function test_national_outage_preserves_local_choices(): void
    {
        $this->directory()->shouldReceive('searchNationwide')->andThrow(new \RuntimeException('Fixture outage'));
        $this->getJson('/admin/school-picker/search?q=Registered')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 1)->assertJsonPath('message', 'Direktori nasional belum dapat dihubungi. Sekolah terdaftar tetap bisa dipilih; coba lagi nanti.');
    }

    public function test_register_validates_directory_and_pauses_reporting_without_creating_account(): void
    {
        $mock = $this->directory();
        $mock->shouldReceive('guestPreview')->once()->with('10101010')->andReturn(['province_code' => '01', 'regency_code' => '01.01', 'district_code' => '01.01.01']);
        $mock->shouldReceive('import')->once()->with('10101010', '01', '01.01', '01.01.01')->andReturnUsing(function () {
            $id = DB::table('schools')->insertGetId(['name' => 'Verified National School', 'npsn' => '10101010']);

            return School::findOrFail($id);
        });
        $this->postJson('/admin/school-picker/register', ['npsn' => '10101010', 'name' => 'Forged name', 'latitude' => 1])->assertOk()->assertJsonPath('data.name', 'Verified National School');
        $school = School::where('npsn', '10101010')->firstOrFail();
        $this->assertSame('paused', $school->status);
        $this->assertFalse($school->features['reporting']);
        $this->assertNotEmpty($school->public_slug);
        $this->assertSame(2, DB::table('users')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'school.directory_registered', 'actor_id' => 1, 'school_id' => $school->id]);
    }

    public function test_existing_school_is_selected_without_import_or_duplicate(): void
    {
        $this->directory();
        $this->postJson('/admin/school-picker/register', ['npsn' => '20202020'])->assertOk()->assertJsonPath('data.id', 1);
        $this->assertSame(1, DB::table('schools')->count());
        $this->assertSame('active', School::find(1)->status);
    }

    public function test_archived_school_cannot_be_silently_restored_or_shown_in_search(): void
    {
        DB::table('schools')->where('id', 1)->update(['status' => 'archived']);
        $this->directory()->shouldReceive('searchNationwide')->andReturn([['name' => 'Registered School', 'npsn' => '20202020']]);
        $this->getJson('/admin/school-picker/search?q=School')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/admin/school-picker/register', ['npsn' => '20202020'])->assertStatus(422);
        $this->assertSame('archived', School::find(1)->status);
    }

    public function test_school_admin_is_denied_search_and_register(): void
    {
        $this->loginAs(2);
        $this->directory();
        $this->getJson('/admin/school-picker/search?q=School')->assertForbidden();
        $this->postJson('/admin/school-picker/register', ['npsn' => '10101010'])->assertForbidden();
        $this->assertSame(1, DB::table('schools')->count());
    }

    public function test_registration_requires_reauthentication(): void
    {
        $this->loginAs(1, false);
        $this->directory();
        $this->postJson('/admin/school-picker/register', ['npsn' => '10101010'])->assertStatus(428);
        $this->assertSame(1, DB::table('schools')->count());
    }

    public function test_short_search_and_invalid_npsn_are_rejected(): void
    {
        $this->directory();
        $this->getJson('/admin/school-picker/search?q=ab')->assertUnprocessable();
        $this->postJson('/admin/school-picker/register', ['npsn' => '../invalid'])->assertUnprocessable();
    }
}
