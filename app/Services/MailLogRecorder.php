<?php

namespace App\Services;

use App\Models\EmailDeliveryLog;
use App\Models\User;

/**
 * Mencatat setiap pengiriman email ke email_delivery_logs tanpa pernah
 * menyimpan isi email, URL bertanda tangan, token, atau kredensial.
 * Kesalahan disimpan dalam bentuk yang sudah dibersihkan dari tautan
 * dan alamat email agar aman ditampilkan di dashboard admin.
 */
final class MailLogRecorder
{
    public function start(?User $user, string $recipient, string $category, string $subject, ?string $dedupeKey = null, ?array $payload = null, ?int $notificationRecipientId = null): EmailDeliveryLog
    {
        return EmailDeliveryLog::create([
            'user_id' => $user?->id,
            'notification_recipient_id' => $notificationRecipientId,
            'recipient_hash' => EmailDeliveryLog::hashRecipient($recipient),
            'recipient_masked' => EmailDeliveryLog::maskRecipient($recipient),
            'category' => $category,
            'subject' => $subject,
            'status' => 'queued',
            'attempts' => 0,
            'payload' => $payload,
            'dedupe_key' => $dedupeKey,
            'queued_at' => now(),
        ]);
    }

    /**
     * Untuk peristiwa berkunci dedupe: kembalikan null bila email yang sama
     * sudah antre/terkirim, agar peristiwa berulang tidak membanjiri inbox.
     */
    public function startOnce(?User $user, string $recipient, string $category, string $subject, string $dedupeKey, ?array $payload = null, ?int $notificationRecipientId = null): ?EmailDeliveryLog
    {
        $existing = EmailDeliveryLog::where('dedupe_key', $dedupeKey)->first();
        if ($existing) {
            return $existing->status === 'failed' ? $existing : null;
        }

        return $this->start($user, $recipient, $category, $subject, $dedupeKey, $payload, $notificationRecipientId);
    }

    public function markSent(EmailDeliveryLog $log): void
    {
        $log->forceFill(['status' => 'sent', 'sent_at' => now(), 'failed_at' => null, 'error' => null])->save();
    }

    public function markFailed(EmailDeliveryLog $log, \Throwable $e): void
    {
        $log->forceFill(['status' => 'failed', 'failed_at' => now(), 'error' => self::sanitizeError($e)])->save();
    }

    /** Catat hasil pengiriman sinkron yang sudah terjadi (berhasil/gagal). */
    public function recordOutcome(?User $user, string $recipient, string $category, string $subject, bool $sent, ?\Throwable $error = null, ?int $notificationRecipientId = null): EmailDeliveryLog
    {
        $log = $this->start($user, $recipient, $category, $subject, null, null, $notificationRecipientId);
        if ($sent) {
            $this->markSent($log);
        } elseif ($error) {
            $this->markFailed($log, $error);
        } else {
            $log->forceFill(['status' => 'failed', 'failed_at' => now(), 'error' => 'Pengiriman gagal tanpa rincian dari mailer.'])->save();
        }

        return $log;
    }

    public static function sanitizeError(\Throwable $e): string
    {
        $message = $e->getMessage();
        $message = preg_replace('#https?://\S+#i', '[tautan]', $message) ?? $message;
        $message = preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '[email]', $message) ?? $message;
        $message = preg_replace('/\b[A-Za-z0-9_\-]{32,}\b/', '[token]', $message) ?? $message;

        return mb_substr(trim($message) === '' ? class_basename($e) : trim($message), 0, 300);
    }
}
