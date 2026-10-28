<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminActivityRowsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->unsignedBigInteger('school_id')->nullable();
        });
        DB::table('users')->insert([
            ['id' => 1, 'display_name' => 'CurrentActor', 'school_id' => 7],
            ['id' => 4, 'display_name' => 'CurrentTarget', 'school_id' => 7],
            ['id' => 9, 'display_name' => 'OutsideSchool', 'school_id' => 8],
        ]);
        $this->viewer('owner');
    }

    private function viewer(string $role): void
    {
        Auth::setUser((new User)->forceFill(['id' => 77, 'role' => $role, 'school_id' => 7]));
    }

    private function row(array $attributes = []): AuditLog
    {
        return (new AuditLog)->forceFill(array_replace([
            'id' => 20, 'action' => 'admin.permissions_changed', 'actor_id' => 1,
            'entity' => 'user', 'entity_id' => 4, 'created_at' => '2026-10-10 02:00:00',
            'actor_name' => null, 'target_label' => null, 'occurred_at' => null,
        ], $attributes));
    }

    private function renderRows(array $rows): string
    {
        return view('admin.log-rows', ['logs' => collect($rows)])->render();
    }

    public function test_historical_snapshots_take_priority_and_actions_are_labelled(): void
    {
        DB::enableQueryLog();
        $html = $this->renderRows([$this->row(['actor_name' => 'HistoricalActor', 'target_label' => 'HistoricalTarget'])]);
        $this->assertStringContainsString('Pelaku: HistoricalActor', $html);
        $this->assertStringContainsString('Target: HistoricalTarget', $html);
        $this->assertStringContainsString('Mengubah izin', $html);
        $this->assertStringContainsString('10/10 09:00 WIB', $html);
        $this->assertStringNotContainsString('CurrentActor', $html);
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_legacy_current_names_are_explicit_and_batch_fetched(): void
    {
        DB::enableQueryLog();
        $html = $this->renderRows([$this->row(), $this->row(['id' => 21])]);
        $this->assertStringContainsString('Pelaku: CurrentActor (nama akun saat ini)', $html);
        $this->assertStringContainsString('Target: CurrentTarget (nama akun saat ini)', $html);
        $this->assertStringContainsString('snapshot nama belum tersedia', $html);
        $this->assertStringNotContainsString('user #4', $html);
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_school_viewer_does_not_lookup_current_names_outside_scope(): void
    {
        $this->viewer('school_admin');
        $html = $this->renderRows([$this->row(['actor_id' => 9, 'entity_id' => 9])]);
        $this->assertStringNotContainsString('OutsideSchool', $html);
        $this->assertStringContainsString('Pelaku #9', $html);
        $this->assertStringContainsString('Akun #9', $html);
    }

    public function test_missing_actor_is_not_invented_as_system(): void
    {
        $html = $this->renderRows([$this->row(['actor_id' => null, 'entity' => 'school', 'entity_id' => 5])]);
        $this->assertStringContainsString('Pelaku tidak tercatat', $html);
        $this->assertStringContainsString('Sekolah #5', $html);
    }

    public function test_snapshot_names_are_html_escaped(): void
    {
        $html = $this->renderRows([$this->row(['actor_name' => '<script>alert(1)</script>', 'target_label' => '<b>target</b>'])]);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;target&lt;/b&gt;', $html);
    }

    public function test_empty_activity_keeps_empty_state(): void
    {
        $this->assertStringContainsString('Belum ada aktivitas', $this->renderRows([]));
    }
}
