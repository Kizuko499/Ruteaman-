<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris per periode ringkasan per penerima. Unik (recipient_id,
 * period_key) adalah kunci anti-ganda: scheduler boleh berjalan berkali-kali,
 * periode yang sama tidak pernah dikirim dua kali.
 */
class NotificationDigestRun extends Model
{
    public const STATUS_LABELS = [
        'queued' => 'Dalam antrean',
        'sent' => 'Terkirim',
        'failed' => 'Gagal',
        'skipped_empty' => 'Dilewati (ringkasan kosong)',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(NotificationRecipient::class, 'recipient_id');
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailDeliveryLog::class, 'email_log_id');
    }

    public function statusText(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
