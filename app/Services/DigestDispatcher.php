<?php

namespace App\Services;

use App\Jobs\DeliverEventMail;
use App\Mail\DigestSummaryMail;
use App\Models\EmailDeliveryLog;
use App\Models\NotificationDigestRun;
use App\Models\NotificationRecipient;
use Carbon\Carbon;

/**
 * Menjalankan ringkasan berkala yang jatuh tempo. Dipanggil command
 * notifications:dispatch-digests dari scheduler (tiap 15 menit).
 *
 * Jaminan anti-ganda:
 * - Periode diturunkan dari jadwal tersimpan (next_run_at) dan riwayat run,
 *   bukan dari waktu proses, sehingga keterlambatan scheduler tidak menggeser
 *   irama maupun menggandakan periode.
 * - (recipient_id, period_key) unik di database; run yang sudah memiliki
 *   email_log_id tidak pernah dikirim ulang.
 * - next_run_at selalu dimajukan melewati waktu sekarang setelah diproses.
 */
final class DigestDispatcher
{
    public function __construct(
        private MailLogRecorder $recorder,
        private DigestScheduleCalculator $calculator,
        private DigestBuilder $builder,
    ) {}

    /** @return array{sent: int, skipped_empty: int, postponed: int, already: int} */
    public function run(): array
    {
        $stats = ['sent' => 0, 'skipped_empty' => 0, 'postponed' => 0, 'already' => 0];
        NotificationRecipient::dueForDigest()->orderBy('next_run_at')->chunkById(50, function ($recipients) use (&$stats) {
            foreach ($recipients as $recipient) {
                $stats[$this->dispatchFor($recipient)]++;
            }
        });

        return $stats;
    }

    private function dispatchFor(NotificationRecipient $recipient): string
    {
        $this->syncRuns($recipient);
        $now = Carbon::now('UTC');

        // Ringkasan bersifat nonkritis: jangan kirim di dalam jam tenang.
        if ($this->calculator->inQuietHours($recipient, $now)) {
            $recipient->forceFill(['next_run_at' => $this->calculator->quietEndAfter($recipient, $now)])->save();

            return 'postponed';
        }

        $tz = $this->calculator->timezone($recipient);
        $scheduledEnd = $recipient->next_run_at->copy();
        $periodEndDate = $scheduledEnd->copy()->setTimezone($tz)->toDateString();
        $lastRun = $recipient->digestRuns()->orderByDesc('period_end')->first();
        $periodStartDate = $lastRun
            ? Carbon::parse($lastRun->period_end->toDateString())->addDay()->toDateString()
            : $recipient->created_at->copy()->setTimezone($tz)->toDateString();
        if ($periodStartDate > $periodEndDate) {
            $periodStartDate = $periodEndDate;
        }
        $periodKey = $periodStartDate.'/'.$periodEndDate;

        $run = NotificationDigestRun::firstOrCreate(
            ['recipient_id' => $recipient->id, 'period_key' => $periodKey],
            ['period_start' => $periodStartDate, 'period_end' => $periodEndDate, 'status' => 'queued'],
        );
        if ($run->email_log_id || $run->status !== 'queued') {
            $this->advance($recipient, $scheduledEnd);

            return 'already';
        }

        $sections = $this->builder->build(
            $recipient,
            Carbon::parse($periodStartDate, $tz),
            Carbon::parse($periodEndDate, $tz),
        );
        if ($sections === [] && ! $recipient->send_empty_digest) {
            $run->forceFill(['status' => 'skipped_empty'])->save();
            $this->advance($recipient, $scheduledEnd);

            return 'skipped_empty';
        }
        if ($sections === []) {
            $sections = [['title' => 'Ringkasan', 'lines' => ['Tidak ada aktivitas baru pada periode ini.']]];
        }

        $periodLabel = Carbon::parse($periodStartDate)->locale('id')->translatedFormat('d F Y')
            .' – '.Carbon::parse($periodEndDate)->locale('id')->translatedFormat('d F Y');
        $subject = 'Ringkasan RuteAman — '.$periodLabel;
        $args = [$recipient->name, $periodLabel, $sections, url('/')];
        $log = $this->recorder->start(null, $recipient->email, 'digest.summary', $subject, 'digest:'.$recipient->id.':'.$periodKey, ['mailable' => DigestSummaryMail::class, 'args' => $args], $recipient->id);
        DeliverEventMail::dispatch($log->id, $recipient->email, DigestSummaryMail::class, $args)->afterCommit();
        $run->forceFill(['email_log_id' => $log->id])->save();
        $this->advance($recipient, $scheduledEnd);

        return 'sent';
    }

    /** Majukan next_run_at dari jadwal yang direncanakan sampai melewati sekarang. */
    private function advance(NotificationRecipient $recipient, Carbon $scheduled): void
    {
        $next = $this->calculator->nextRunAfter($recipient, $scheduled);
        $guard = 0;
        while ($next->lessThanOrEqualTo(Carbon::now('UTC')) && $guard++ < 400) {
            $next = $this->calculator->nextRunAfter($recipient, $next);
        }
        $recipient->forceFill(['next_run_at' => $next])->save();
    }

    /** Samakan status run dengan status log pengirimannya; perbarui last_sent_at penerima. */
    private function syncRuns(NotificationRecipient $recipient): void
    {
        $runs = $recipient->digestRuns()->where('status', 'queued')->whereNotNull('email_log_id')->get();
        foreach ($runs as $run) {
            $log = EmailDeliveryLog::find($run->email_log_id);
            if (! $log) {
                continue;
            }
            if ($log->status === 'sent') {
                $run->forceFill(['status' => 'sent'])->save();
                if ($log->sent_at && (! $recipient->last_sent_at || $log->sent_at->greaterThan($recipient->last_sent_at))) {
                    $recipient->forceFill(['last_sent_at' => $log->sent_at])->save();
                }
            } elseif (in_array($log->status, ['failed', 'cancelled'], true)) {
                $run->forceFill(['status' => 'failed'])->save();
            }
        }
    }
}
