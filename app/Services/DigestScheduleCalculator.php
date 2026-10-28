<?php

namespace App\Services;

use App\Models\NotificationRecipient;
use Carbon\Carbon;
use DateTimeZone;

/**
 * Menghitung jadwal ringkasan berikutnya secara murni dari konfigurasi
 * penerima. Semua perhitungan dilakukan pada zona waktu penerima lalu
 * disimpan sebagai UTC. Aturan tepi yang dijaga:
 * - Bulanan tanggal 31 pada bulan pendek dijepit ke hari terakhir bulan itu.
 * - Mode bulanan "last" selalu hari terakhir bulan berjalan berikutnya.
 * - Jadwal tidak pernah berada di dalam jam tenang: digeser ke akhir jam tenang.
 * - nextRunAfter berjangkar pada jadwal yang direncanakan (bukan waktu proses)
 *   agar irama pengiriman stabil walau scheduler terlambat berjalan.
 */
final class DigestScheduleCalculator
{
    public function timezone(NotificationRecipient $r): DateTimeZone
    {
        try {
            return new DateTimeZone($r->timezone ?: 'Asia/Jakarta');
        } catch (\Throwable) {
            return new DateTimeZone('Asia/Jakarta');
        }
    }

    private function sendTimeParts(NotificationRecipient $r): array
    {
        $parts = explode(':', (string) ($r->send_time ?: '08:00'));

        return [(int) ($parts[0] ?? 8), (int) ($parts[1] ?? 0)];
    }

    private function atSendTime(NotificationRecipient $r, Carbon $localDate): Carbon
    {
        [$h, $m] = $this->sendTimeParts($r);

        return $localDate->copy()->startOfDay()->setTime($h, $m, 0);
    }

    /** Jadwal pertama setelah penerima dibuat/diaktifkan. Null untuk mode realtime murni. */
    public function initialNextRun(NotificationRecipient $r, ?Carbon $now = null): ?Carbon
    {
        if ($r->mode === 'realtime') {
            return null;
        }
        $now ??= Carbon::now('UTC');

        return match ($r->frequency_type) {
            'weekly' => $this->weeklyNext($r, $now),
            'monthly' => $this->monthlyNext($r, $now),
            default => $this->intervalNext($r, $now, $r->last_sent_at ?? $r->created_at ?? $now),
        };
    }

    /** Jadwal berikutnya secara ketat setelah $after (dipakai dispatcher untuk memajukan irama). */
    public function nextRunAfter(NotificationRecipient $r, Carbon $after): Carbon
    {
        return match ($r->frequency_type) {
            'weekly' => $this->weeklyNext($r, $after),
            'monthly' => $this->monthlyNext($r, $after),
            default => $this->intervalNext($r, $after, $after),
        };
    }

    private function intervalNext(NotificationRecipient $r, Carbon $afterUtc, Carbon $anchorUtc): Carbon
    {
        $tz = $this->timezone($r);
        $after = $afterUtc->copy()->setTimezone($tz);
        $candidate = $this->atSendTime($r, $anchorUtc->copy()->setTimezone($tz));
        $days = max(1, (int) ($r->interval_days ?? 1));
        if ($candidate->lessThanOrEqualTo($anchorUtc->copy()->setTimezone($tz))) {
            $candidate->addDays($days);
        }
        while ($candidate->lessThanOrEqualTo($after)) {
            $candidate->addDays($days);
        }

        return $this->adjustForQuiet($r, $candidate);
    }

    private function weeklyNext(NotificationRecipient $r, Carbon $afterUtc): Carbon
    {
        $tz = $this->timezone($r);
        $after = $afterUtc->copy()->setTimezone($tz);
        $day = (int) ($r->weekly_day ?? 1);
        for ($i = 0; $i <= 7; $i++) {
            $date = $after->copy()->startOfDay()->addDays($i);
            if ((int) $date->isoWeekday() !== $day) {
                continue;
            }
            $candidate = $this->atSendTime($r, $date);
            if ($candidate->greaterThan($after)) {
                return $this->adjustForQuiet($r, $candidate);
            }
        }

        return $this->adjustForQuiet($r, $this->atSendTime($r, $after->copy()->startOfDay()->addDays(7)));
    }

    private function monthlyNext(NotificationRecipient $r, Carbon $afterUtc): Carbon
    {
        $tz = $this->timezone($r);
        $after = $afterUtc->copy()->setTimezone($tz);
        for ($i = 0; $i <= 2; $i++) {
            $month = $after->copy()->startOfMonth()->addMonthsNoOverflow($i);
            $candidate = $this->atSendTime($r, $month->copy()->day($this->monthlyDay($r, $month)));
            if ($candidate->greaterThan($after)) {
                return $this->adjustForQuiet($r, $candidate);
            }
        }

        $month = $after->copy()->startOfMonth()->addMonthsNoOverflow(3);

        return $this->adjustForQuiet($r, $this->atSendTime($r, $month->copy()->day($this->monthlyDay($r, $month))));
    }

    private function monthlyDay(NotificationRecipient $r, Carbon $month): int
    {
        return match ($r->monthly_mode) {
            'first' => 1,
            'last' => $month->daysInMonth,
            default => min(max(1, (int) ($r->monthly_day ?? 1)), $month->daysInMonth),
        };
    }

    // ----- Jam tenang -----

    public function inQuietHours(NotificationRecipient $r, Carbon $utc): bool
    {
        if (! $r->quiet_start || ! $r->quiet_end) {
            return false;
        }
        $local = $utc->copy()->setTimezone($this->timezone($r));
        $t = (int) $local->format('H') * 60 + (int) $local->format('i');
        $s = $this->minutesOf((string) $r->quiet_start);
        $e = $this->minutesOf((string) $r->quiet_end);
        if ($s === $e) {
            return false;
        }

        return $s < $e ? ($t >= $s && $t < $e) : ($t >= $s || $t < $e);
    }

    /** Waktu (UTC) ketika jam tenang berakhir, dihitung dari $utc yang sedang di dalam jam tenang. */
    public function quietEndAfter(NotificationRecipient $r, Carbon $utc): Carbon
    {
        $tz = $this->timezone($r);
        $local = $utc->copy()->setTimezone($tz);
        $e = $this->minutesOf((string) $r->quiet_end);
        $end = $local->copy()->startOfDay()->addMinutes($e);
        if ($end->lessThanOrEqualTo($local)) {
            $end->addDay();
        }

        return $end->copy()->setTimezone('UTC');
    }

    private function adjustForQuiet(NotificationRecipient $r, Carbon $localCandidate): Carbon
    {
        $utc = $localCandidate->copy()->setTimezone('UTC');
        if ($this->inQuietHours($r, $utc)) {
            return $this->quietEndAfter($r, $utc);
        }

        return $utc;
    }

    private function minutesOf(string $time): int
    {
        $parts = explode(':', $time);

        return (int) ($parts[0] ?? 0) * 60 + (int) ($parts[1] ?? 0);
    }
}
