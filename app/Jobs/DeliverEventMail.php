<?php

namespace App\Jobs;

use App\Models\EmailDeliveryLog;
use App\Services\MailLogRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Mengirim email peristiwa (laporan) melalui antrean. Payload hanya berisi
 * id log, alamat penerima, kelas mailable, dan argumen skalar pembentuknya —
 * tidak pernah URL bertanda tangan atau token. Email keamanan akun
 * (verifikasi/undangan) SENGAJA tidak memakai job ini; email tersebut tetap
 * dikirim sinkron sesuai keputusan keamanan pada AdminAccountService.
 */
class DeliverEventMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $logId,
        public string $recipientEmail,
        public string $mailableClass,
        public array $mailableArgs = [],
    ) {}

    public function handle(MailLogRecorder $recorder): void
    {
        $log = EmailDeliveryLog::find($this->logId);
        if (! $log || $log->status === 'sent' || $log->status === 'cancelled') {
            return;
        }
        // Penerima mandiri yang dinonaktifkan/dihapus setelah email mengantre
        // tidak boleh menerima pengiriman ini.
        if ($log->notification_recipient_id) {
            $recipient = \App\Models\NotificationRecipient::find($log->notification_recipient_id);
            if (! $recipient || ! $recipient->is_active || ! $recipient->verified_at) {
                $log->forceFill(['status' => 'cancelled', 'error' => 'Dibatalkan otomatis: penerima notifikasi tidak lagi aktif/terverifikasi.'])->save();

                return;
            }
        }
        $log->forceFill(['attempts' => max($log->attempts, $this->attempts())])->save();

        $mailable = new ($this->mailableClass)(...$this->mailableArgs);
        Mail::to($this->recipientEmail)->send($mailable);

        $recorder->markSent($log);
    }

    public function failed(\Throwable $e): void
    {
        $log = EmailDeliveryLog::find($this->logId);
        if ($log && $log->status !== 'sent') {
            app(MailLogRecorder::class)->markFailed($log, $e);
        }
    }
}
