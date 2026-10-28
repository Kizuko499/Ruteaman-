<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminSchoolDataController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentViewRemovalTest extends TestCase
{
    public function test_parent_feature_and_permission_dependency_are_removed(): void
    {
        $this->assertArrayNotHasKey('parent_view', config('ruteaman_admin.features'));
        $this->assertArrayNotHasKey('parent_view', config('ruteaman_admin.feature_labels'));
        $this->assertSame(['geojson_feed'], config('ruteaman_permissions')['feed.manage']['features']);
        $this->assertSame('Atur feed GeoJSON', config('ruteaman_permissions')['feed.manage']['label']);
        $this->assertStringNotContainsString('orang tua', json_encode(config('ruteaman_permissions')));
    }

    public function test_parent_route_and_controller_action_are_gone(): void
    {
        $this->assertFalse(Route::has('parent.board'));
        $this->assertFalse(method_exists(AdminSchoolDataController::class, 'parent'));
        $this->get('/parent/old-school')->assertNotFound();
    }

    public function test_retirement_migration_removes_only_parent_feature_and_is_idempotent(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->text('features')->nullable();
            $t->text('settings')->nullable();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->timestamp('parental_consent_at')->nullable();
        });
        $a = DB::table('schools')->insertGetId(['features' => json_encode(['parent_view' => true, 'geojson_feed' => true, 'reporting' => false, 'require_guest_review' => true, 'require_account_review' => false]), 'settings' => json_encode(['public_escalation_status' => true])]);
        $b = DB::table('schools')->insertGetId(['features' => json_encode(['parent_view' => false, 'trend' => true])]);
        $null = DB::table('schools')->insertGetId(['features' => null]);
        $invalid = DB::table('schools')->insertGetId(['features' => 'invalid-json']);
        DB::table('users')->insert(['parental_consent_at' => '2026-10-10 00:00:00']);
        $migration = require database_path('migrations/2026_10_18_000000_retire_parent_view_feature.php');
        $migration->up();
        $snapshot = DB::table('schools')->orderBy('id')->get()->toJson();
        $migration->up();
        $this->assertSame($snapshot, DB::table('schools')->orderBy('id')->get()->toJson());
        $features = json_decode(DB::table('schools')->where('id', $a)->value('features'), true);
        $this->assertSame(['geojson_feed' => true, 'reporting' => false, 'require_guest_review' => true, 'require_account_review' => false], $features);
        $this->assertSame(['trend' => true], json_decode(DB::table('schools')->where('id', $b)->value('features'), true));
        $this->assertNull(DB::table('schools')->where('id', $null)->value('features'));
        $this->assertSame('invalid-json', DB::table('schools')->where('id', $invalid)->value('features'));
        $this->assertSame('{"public_escalation_status":true}', DB::table('schools')->where('id', $a)->value('settings'));
        $this->assertSame('2026-10-10 00:00:00', DB::table('users')->value('parental_consent_at'));
    }
}
