<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Support\AuditPresenter;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditPresenterTest extends TestCase
{
    private function log(array $attrs): AuditLog
    {
        $m = new AuditLog;
        $m->forceFill($attrs);

        return $m;
    }

    public function test_device_summary_summarizes_user_agent(): void
    {
        $this->assertSame('Chrome · Android (HP)', AuditPresenter::deviceSummary('Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 Chrome/156.0.0.0 Mobile Safari/537.36'));
        $this->assertSame('Firefox · Windows', AuditPresenter::deviceSummary('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/140.0'));
        $this->assertSame('Tidak dikenal', AuditPresenter::deviceSummary(null));
    }

    public function test_meta_rows_skip_nulls_and_summarize_permission_sets(): void
    {
        $log = $this->log(['meta' => ['region_id' => null, 'permissions' => ['added' => ['a', 'b'], 'removed' => ['x']]]]);
        $this->assertSame([['Izin', '2 ditambah · 1 dicabut']], AuditPresenter::metaRows($log));
    }

    public function test_relative_time_and_role_labels(): void
    {
        $log = $this->log(['actor_role' => 'owner', 'occurred_at' => Carbon::now()->subMinutes(5)]);
        $this->assertSame('Admin Utama', AuditPresenter::roleLabel($log));
        $this->assertSame('5 menit yang lalu', AuditPresenter::relativeTime($log, 'Asia/Jakarta'));
    }
}
