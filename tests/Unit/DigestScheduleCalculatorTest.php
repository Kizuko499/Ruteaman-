<?php

namespace Tests\Unit;

use App\Models\NotificationRecipient;
use App\Services\DigestScheduleCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class DigestScheduleCalculatorTest extends TestCase
{
    private DigestScheduleCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new DigestScheduleCalculator;
    }

    private function recipient(array $attrs): NotificationRecipient
    {
        $r = new NotificationRecipient;
        $r->forceFill(array_merge([
            'mode' => 'digest',
            'frequency_type' => 'weekly',
            'weekly_day' => 1,
            'send_time' => '08:00:00',
            'timezone' => 'Asia/Jakarta',
        ], $attrs));

        return $r;
    }

    public function test_weekly_next_is_coming_monday_at_send_time_jakarta(): void
    {
        $r = $this->recipient([]);
        // Jumat 23 Okt 2026 10:00 WIB -> Senin 26 Okt 2026 08:00 WIB = 01:00 UTC
        $next = $this->calc->nextRunAfter($r, Carbon::parse('2026-10-23 03:00:00', 'UTC'));
        $this->assertSame('2026-10-26 01:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_monthly_date_31_clamps_to_february_last_day(): void
    {
        $r = $this->recipient(['frequency_type' => 'monthly', 'monthly_mode' => 'date', 'monthly_day' => 31]);
        // Setelah jadwal 31 Jan 2026 08:00 WIB -> berikutnya 28 Feb 2026 08:00 WIB
        $next = $this->calc->nextRunAfter($r, Carbon::parse('2026-01-31 01:00:00', 'UTC'));
        $this->assertSame('2026-02-28 01:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_monthly_last_mode_uses_month_end(): void
    {
        $r = $this->recipient(['frequency_type' => 'monthly', 'monthly_mode' => 'last', 'monthly_day' => null]);
        $next = $this->calc->nextRunAfter($r, Carbon::parse('2026-02-10 00:00:00', 'UTC'));
        $this->assertSame('2026-02-28 01:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_monthly_first_rolls_to_next_month_after_send_time(): void
    {
        $r = $this->recipient(['frequency_type' => 'monthly', 'monthly_mode' => 'first', 'monthly_day' => null]);
        // 1 Feb 2026 09:00 WIB (lewat jam kirim) -> 1 Mar 2026 08:00 WIB
        $next = $this->calc->nextRunAfter($r, Carbon::parse('2026-02-01 02:00:00', 'UTC'));
        $this->assertSame('2026-03-01 01:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_interval_advances_from_scheduled_anchor(): void
    {
        $r = $this->recipient(['frequency_type' => 'interval', 'interval_days' => 3]);
        // Jadwal terakhir Senin 19 Okt 08:00 WIB -> +3 hari berulang sampai melewati "after"
        $next = $this->calc->nextRunAfter($r, Carbon::parse('2026-10-19 01:00:00', 'UTC'));
        $this->assertSame('2026-10-22 01:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_quiet_hours_shift_digest_out_of_window(): void
    {
        $r = $this->recipient(['quiet_start' => '07:00:00', 'quiet_end' => '09:00:00']);
        // Senin 08:00 WIB berada di jam tenang 07:00-09:00 -> digeser ke 09:00 WIB
        $next = $this->calc->nextRunAfter($r, Carbon::parse('2026-10-23 03:00:00', 'UTC'));
        $this->assertSame('2026-10-26 02:00:00', $next->format('Y-m-d H:i:s'));
        $this->assertTrue($this->calc->inQuietHours($r, Carbon::parse('2026-10-26 01:00:00', 'UTC')));
        $this->assertFalse($this->calc->inQuietHours($r, Carbon::parse('2026-10-26 02:00:00', 'UTC')));
    }

    public function test_realtime_mode_has_no_initial_schedule(): void
    {
        $r = $this->recipient(['mode' => 'realtime']);
        $this->assertNull($this->calc->initialNextRun($r, Carbon::parse('2026-10-23 00:00:00', 'UTC')));
    }
}
