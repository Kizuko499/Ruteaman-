<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDeliveryLog extends Model
{
    /** Label tampilan untuk setiap kategori email yang dicatat sistem. */
    public const CATEGORY_LABELS = [
        'admin.test' => 'Email uji',
        'account.verification' => 'Verifikasi akun',
        'account.invitation' => 'Undangan admin',
        'account.created_notice' => 'Pemberitahuan akun dibuat',
        'admin.notice' => 'Pemberitahuan admin',
        'admin.broadcast' => 'Informasi admin',
        'report.submitted' => 'Laporan baru masuk',
        'report.status_changed' => 'Perubahan status laporan',
        'report.comment_added' => 'Komentar baru pada laporan',
        'digest.summary' => 'Ringkasan berkala',
        'digest.reports' => 'Ringkasan laporan',
        'digest.schools' => 'Perubahan data institusi',
        'digest.accounts' => 'Aktivitas akun & keamanan',
        'digest.system' => 'Kesehatan sistem email',
        'recipient.verification' => 'Verifikasi penerima notifikasi',
        'report.escalation' => 'Eskalasi & tindak lanjut laporan',
        'report.expired' => 'Laporan kedaluwarsa',
        'school.proposal' => 'Usulan institusi baru',
        'school.registered' => 'Institusi baru terdaftar',
        'site.announcement' => 'Pengumuman situs',
        'site.maintenance' => 'Pemeliharaan & gangguan layanan',
    ];

    /** Label tampilan untuk status pengiriman. */
    public const STATUS_LABELS = [
        'queued' => 'Dalam antrean',
        'sent' => 'Terkirim',
        'failed' => 'Gagal',
        'cancelled' => 'Dibatalkan',
    ];

    protected $guarded = [];

    public static function categoryLabel(string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? ucfirst(str_replace(['.', '_'], ' ', $category));
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace(['.', '_'], ' ', $status));
    }

    public function categoryText(): string
    {
        return self::categoryLabel((string) $this->category);
    }

    public function statusText(): string
    {
        return self::statusLabel((string) $this->status);
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notificationRecipient(): BelongsTo
    {
        return $this->belongsTo(NotificationRecipient::class, 'notification_recipient_id');
    }

    public static function hashRecipient(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    public static function maskRecipient(string $email): string
    {
        $email = trim($email);
        $at = strrpos($email, '@');
        if ($at === false) {
            return '***';
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);
        $head = mb_substr($local, 0, 1);

        return ($head === '' ? '*' : $head).'***@'.$domain;
    }
}
