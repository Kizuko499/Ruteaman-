<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Carbon;

/**
 * Penyajian ramah untuk entri Log Audit: label manusia, waktu relatif,
 * ringkasan perangkat, dan baris meta/perubahan yang sudah dihumanisasi.
 * Dipakai view admin/audit agar tidak ada logika berat di dalam Blade.
 */
final class AuditPresenter
{
    public static function actionLabel(AuditLog $log): string
    {
        return (string) (config('ruteaman_audit.labels')[$log->action] ?? $log->action);
    }

    public static function categoryLabel(AuditLog $log): string
    {
        return match ($log->category) {
            'auth' => 'Keamanan akun',
            'hazard', 'report' => 'Laporan kondisi',
            'school' => 'Institusi',
            'user' => 'Pengguna',
            'email' => 'Email & notifikasi',
            'gallery' => 'Galeri beranda',
            default => $log->category ? ucfirst(str_replace('_', ' ', $log->category)) : 'Aktivitas',
        };
    }

    public static function roleLabel(AuditLog $log): ?string
    {
        return match ($log->actor_role) {
            'owner' => 'Admin Utama',
            'school_admin' => 'Admin Sekolah',
            'platform_admin', 'school_staff' => 'Tingkat lama (nonaktif)',
            default => $log->actor_role,
        };
    }

    /** @return array{string, string, string}|null [teks, kelas pill, ikon] */
    public static function resultInfo(AuditLog $log): ?array
    {
        return match ($log->result) {
            'success' => ['✔ Berhasil', 'ok', '✔'],
            'denied' => ['⊘ Ditolak', 'deny', '⊘'],
            'failed' => ['✕ Gagal', 'bad', '✕'],
            default => null,
        };
    }

    /** @return array{string, string}|null */
    public static function severityInfo(AuditLog $log): ?array
    {
        return match ($log->severity) {
            'critical' => ['⚠ Kritis', 'crit'],
            'warning' => ['⚠ Peringatan', 'warn'],
            default => null,
        };
    }

    public static function when(AuditLog $log): ?Carbon
    {
        return $log->occurred_at ?? $log->created_at;
    }

    public static function relativeTime(AuditLog $log, string $tz): ?string
    {
        $when = self::when($log);
        if (! $when) {
            return null;
        }
        $sec = (int) $when->diffInSeconds(now());
        if ($sec < 60) {
            return 'baru saja';
        }
        if ($sec < 3600) {
            return ((int) floor($sec / 60)).' menit yang lalu';
        }
        if ($sec < 86400) {
            return ((int) floor($sec / 3600)).' jam yang lalu';
        }
        if ($sec < 30 * 86400) {
            return ((int) floor($sec / 86400)).' hari yang lalu';
        }

        return $when->copy()->timezone($tz)->format('d M Y');
    }

    public static function whenText(AuditLog $log, string $tz): string
    {
        $when = self::when($log);
        if (! $when) {
            return '—';
        }
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $local = $when->copy()->timezone($tz);

        return $days[$local->dayOfWeek].', '.$local->format('d M Y · H:i');
    }

    public static function targetTypeLabel(AuditLog $log): string
    {
        return match ($log->target_type ?? $log->entity) {
            'user', 'App\Models\User' => 'Akun pengguna',
            'school', 'App\Models\School' => 'Institusi',
            'hazard', 'App\Models\Hazard' => 'Laporan kondisi',
            'region' => 'Wilayah',
            default => ucfirst(str_replace('_', ' ', (string) ($log->target_type ?? $log->entity))),
        };
    }

    public static function deviceSummary(?string $ua): string
    {
        $ua = (string) $ua;
        $os = str_contains($ua, 'Android') ? 'Android'
            : ((str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) ? 'iOS'
            : (str_contains($ua, 'Windows') ? 'Windows'
            : ((str_contains($ua, 'Mac OS') || str_contains($ua, 'Macintosh')) ? 'macOS'
            : (str_contains($ua, 'Linux') ? 'Linux' : null))));
        $br = str_contains($ua, 'Edg/') ? 'Edge'
            : (str_contains($ua, 'Firefox/') ? 'Firefox'
            : (str_contains($ua, 'Chrome/') ? 'Chrome'
            : (str_contains($ua, 'Safari/') ? 'Safari' : null)));
        $device = ($br || $os) ? trim(implode(' · ', array_filter([$br, $os]))) : 'Tidak dikenal';
        if (str_contains($ua, 'Mobile') && in_array($os, ['Android', 'iOS'], true)) {
            $device .= ' (HP)';
        }

        return $device;
    }

    private static function humanKey(string $key): string
    {
        $map = [
            'region_id' => 'Wilayah', 'school_id' => 'Sekolah', 'added' => 'Ditambahkan',
            'removed' => 'Dicabut', 'count' => 'Jumlah', 'total' => 'Total', 'reason' => 'Alasan',
            'status' => 'Status', 'role' => 'Peran', 'name' => 'Nama', 'email' => 'Email',
            'active' => 'Status aktif', 'permissions' => 'Izin', 'phone' => 'Telepon', 'address' => 'Alamat',
        ];

        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    private static function formatValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value)) {
            if (array_key_exists('added', $value) || array_key_exists('removed', $value)) {
                return count($value['added'] ?? []).' ditambah · '.count($value['removed'] ?? []).' dicabut';
            }
            $flat = array_filter($value, fn ($x) => ! is_array($x));

            return count($flat)
                ? implode(', ', array_map(fn ($x) => is_bool($x) ? ($x ? 'Ya' : 'Tidak') : (string) $x, $flat))
                : count($value).' item';
        }

        return (string) $value;
    }

    /** @return list<array{string, string}> */
    public static function metaRows(AuditLog $log): array
    {
        $rows = [];
        foreach (($log->meta ?? $log->meta_json ?? []) as $key => $value) {
            $formatted = self::formatValue($value);
            if ($formatted === null) {
                continue;
            }
            $rows[] = [self::humanKey((string) $key), mb_strlen($formatted) > 140 ? mb_substr($formatted, 0, 140).'…' : $formatted];
        }

        return $rows;
    }

    /** @return list<array{string, string, string}> */
    public static function changeRows(AuditLog $log): array
    {
        $rows = [];
        foreach (($log->changes ?? []) as $field => $diff) {
            $rows[] = [
                self::humanKey((string) $field),
                self::formatValue($diff['before'] ?? null) ?? '—',
                self::formatValue($diff['after'] ?? null) ?? '—',
            ];
        }

        return $rows;
    }
}
