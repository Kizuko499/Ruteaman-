<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Penerima notifikasi email MANDIRI: alamat di luar akun pengguna
 * (misalnya kepala sekolah) yang dikelola administrator. Berbeda dari
 * preferensi per-akun (users.notification_preferences) yang tidak berubah.
 *
 * Aturan keras: alamat yang belum terverifikasi tidak menerima email apa pun
 * selain email verifikasi itu sendiri dan email uji yang sengaja dikirim
 * administrator.
 */
class NotificationRecipient extends Model
{
    public const MODES = ['realtime' => 'Langsung', 'digest' => 'Ringkasan berkala', 'both' => 'Kombinasi'];

    public const FREQUENCIES = ['interval' => 'Setiap beberapa hari', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'];

    public const WEEKDAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'send_empty_digest' => 'boolean',
            'receive_all_reports' => 'boolean',
            'verified_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'next_run_at' => 'datetime',
            'interval_days' => 'integer',
            'weekly_day' => 'integer',
            'monthly_day' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function categoryRows(): HasMany
    {
        return $this->hasMany(NotificationRecipientCategory::class, 'recipient_id');
    }

    public function digestRuns(): HasMany
    {
        return $this->hasMany(NotificationDigestRun::class, 'recipient_id');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(EmailDeliveryLog::class, 'notification_recipient_id');
    }

    /** @return list<string> kunci kategori yang dilanggan penerima ini */
    public function categoryKeys(): array
    {
        return $this->categoryRows()->pluck('category')->all();
    }

    public function subscribesTo(string $category): bool
    {
        return $this->categoryRows()->where('category', $category)->exists();
    }

    /** Ganti seluruh langganan kategori secara atomik. */
    public function syncCategories(array $keys): void
    {
        $keys = array_values(array_unique($keys));
        $this->categoryRows()->delete();
        foreach ($keys as $key) {
            $this->categoryRows()->create(['category' => $key]);
        }
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** Penerima yang berhak menerima email peristiwa/ringkasan saat ini. */
    public function scopeDeliverable(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('verified_at');
    }

    public function scopeDueForDigest(Builder $query): Builder
    {
        return $query->deliverable()
            ->whereIn('mode', ['digest', 'both'])
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }

    public function frequencyLabel(): string
    {
        return match ($this->frequency_type) {
            'interval' => 'Setiap '.max(1, (int) $this->interval_days).' hari',
            'weekly' => 'Setiap '.(self::WEEKDAYS[(int) $this->weekly_day] ?? '?').' pukul '.substr((string) $this->send_time, 0, 5),
            'monthly' => match ($this->monthly_mode) {
                'first' => 'Tanggal 1 setiap bulan pukul '.substr((string) $this->send_time, 0, 5),
                'last' => 'Hari terakhir setiap bulan pukul '.substr((string) $this->send_time, 0, 5),
                default => 'Tanggal '.(int) $this->monthly_day.' setiap bulan pukul '.substr((string) $this->send_time, 0, 5),
            },
            default => $this->frequency_type,
        };
    }

    /** Label kategori untuk tampilan, dari config/notification_mail.php. */
    public static function categoryLabel(string $key): string
    {
        foreach (['realtime', 'digest', 'broadcast'] as $group) {
            $entry = config("notification_mail.{$group}.{$key}");
            if (is_array($entry)) {
                return $entry['label'] ?? $key;
            }
        }

        return EmailDeliveryLog::categoryLabel($key);
    }

    /** Jenis informasi kiriman manual admin: kunci => label. */
    public static function broadcastTypes(): array
    {
        $types = [];
        foreach ((array) config('notification_mail.broadcast', []) as $key => $entry) {
            $types[$key] = $entry['label'] ?? $key;
        }

        return $types;
    }

    /** Semua kunci kategori yang sah untuk formulir, terpisah per kelompok. */
    public static function categoryGroups(bool $platformScope): array
    {
        $groups = [];
        foreach (['realtime' => 'Notifikasi langsung', 'digest' => 'Isi ringkasan berkala'] as $group => $title) {
            $items = [];
            foreach ((array) config("notification_mail.{$group}", []) as $key => $entry) {
                if (! $platformScope && ($entry['platform_only'] ?? false)) {
                    continue;
                }
                $items[$key] = $entry['label'] ?? $key;
            }
            if ($items) {
                $groups[$title] = $items;
            }
        }

        return $groups;
    }

    /** Validasi kunci kategori terhadap cakupan penerima (cegah platform_only bocor ke penerima sekolah). */
    public static function filterCategoriesForScope(array $keys, bool $platformScope): array
    {
        $allowed = [];
        foreach (['realtime', 'digest'] as $group) {
            foreach ((array) config("notification_mail.{$group}", []) as $key => $entry) {
                if (! $platformScope && ($entry['platform_only'] ?? false)) {
                    continue;
                }
                $allowed[] = $key;
            }
        }

        return array_values(array_intersect($keys, $allowed));
    }
}
