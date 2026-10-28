<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\EmailDeliveryLog;
use App\Models\Hazard;
use App\Models\InstitutionChangeLog;
use App\Models\NotificationRecipient;
use Carbon\Carbon;

/**
 * Menyusun isi ringkasan berkala dari data AKTUAL sesuai kategori yang
 * dilanggan penerima dan cakupannya. Bagian yang kosong tidak disertakan;
 * ringkasan dianggap kosong bila tidak ada satu bagian pun berisi data.
 * Kategori platform_only tidak pernah dibangun untuk penerima bercakupan
 * sekolah (least privilege antar sekolah).
 */
final class DigestBuilder
{
    /**
     * @return list<array{title: string, lines: list<string>}>
     */
    public function build(NotificationRecipient $recipient, Carbon $periodStart, Carbon $periodEnd): array
    {
        $sections = [];
        $keys = $recipient->categoryKeys();
        $from = $periodStart->copy()->startOfDay();
        $to = $periodEnd->copy()->endOfDay();

        if (in_array('digest.reports', $keys, true)) {
            $section = $this->reportsSection($recipient, $from, $to);
            if ($section) {
                $sections[] = $section;
            }
        }
        if (in_array('digest.schools', $keys, true)) {
            $section = $this->schoolsSection($recipient, $from, $to);
            if ($section) {
                $sections[] = $section;
            }
        }
        if ($recipient->school_id === null && in_array('digest.accounts', $keys, true)) {
            $section = $this->accountsSection($from, $to);
            if ($section) {
                $sections[] = $section;
            }
        }
        if ($recipient->school_id === null && in_array('digest.system', $keys, true)) {
            $section = $this->systemSection($from, $to);
            if ($section) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    private function scopedHazards(NotificationRecipient $recipient): \Illuminate\Database\Eloquent\Builder
    {
        $query = Hazard::query();
        if ($recipient->school_id) {
            $query->whereHas('reports', fn ($q) => $q->where('school_id', $recipient->school_id));
        }

        return $query;
    }

    private function reportsSection(NotificationRecipient $recipient, Carbon $from, Carbon $to): ?array
    {
        $base = $this->scopedHazards($recipient);
        $new = (clone $base)->whereBetween('first_reported_at', [$from, $to])->count();
        $resolved = (clone $base)->where('status', 'resolved')->whereBetween('updated_at', [$from, $to])->count();
        $inProgress = (clone $base)->whereIn('status', ['new', 'confirmed', 'verified'])->count();
        $followUp = (clone $base)->where('status', 'disputed')->count();

        $lines = [];
        if ($new > 0) {
            $lines[] = "Laporan baru masuk: {$new}";
        }
        if ($resolved > 0) {
            $lines[] = "Laporan selesai pada periode ini: {$resolved}";
        }
        if ($inProgress > 0) {
            $lines[] = "Masih diproses saat ini: {$inProgress}";
        }
        if ($followUp > 0) {
            $lines[] = "Membutuhkan tindak lanjut saat ini: {$followUp}";
        }

        return $lines ? ['title' => 'Laporan Kondisi Jalan', 'lines' => $lines] : null;
    }

    private function schoolsSection(NotificationRecipient $recipient, Carbon $from, Carbon $to): ?array
    {
        $query = InstitutionChangeLog::whereBetween('created_at', [$from, $to]);
        if ($recipient->school_id) {
            $query->where('institution_id', $recipient->school_id);
        }
        $total = (clone $query)->count();
        if ($total === 0) {
            return null;
        }
        $lines = ["Total perubahan data institusi: {$total}"];
        foreach ((clone $query)->selectRaw('change_type, COUNT(*) as c')->groupBy('change_type')->orderByDesc('c')->get() as $row) {
            $label = ['created' => 'Institusi baru ditambahkan', 'updated' => 'Data diperbarui', 'verified' => 'Institusi diverifikasi', 'archived' => 'Institusi diarsipkan', 'restored' => 'Institusi dipulihkan'][$row->change_type] ?? $row->change_type;
            $lines[] = "{$label}: {$row->c}";
        }

        return ['title' => 'Perubahan Data Institusi', 'lines' => $lines];
    }

    private function accountsSection(Carbon $from, Carbon $to): ?array
    {
        $query = AuditLog::whereBetween('created_at', [$from, $to])
            ->where(fn ($q) => $q->where('action', 'like', 'account.%')->orWhere('action', 'like', 'owner.%')->orWhere('action', 'like', 'admin.%'));
        $total = (clone $query)->count();
        if ($total === 0) {
            return null;
        }
        $lines = ["Total aktivitas akun & keamanan: {$total}"];
        foreach ((clone $query)->selectRaw('action, COUNT(*) as c')->groupBy('action')->orderByDesc('c')->limit(5)->get() as $row) {
            $label = AccountNotificationService::LABELS[$row->action] ?? $row->action;
            $lines[] = "{$label}: {$row->c}";
        }

        return ['title' => 'Akun & Keamanan', 'lines' => $lines];
    }

    private function systemSection(Carbon $from, Carbon $to): ?array
    {
        $failed = EmailDeliveryLog::where('status', 'failed')->whereBetween('failed_at', [$from, $to])->count();
        $queued = EmailDeliveryLog::where('status', 'queued')->count();
        $lines = [];
        if ($failed > 0) {
            $lines[] = "Pengiriman email gagal pada periode ini: {$failed}";
            $last = EmailDeliveryLog::where('status', 'failed')->whereBetween('failed_at', [$from, $to])->latest('failed_at')->first();
            if ($last) {
                $lines[] = 'Kegagalan terakhir: '.$last->subject.' — '.mb_substr((string) $last->error, 0, 120);
            }
        }
        if ($queued > 0) {
            $lines[] = "Email masih dalam antrean saat ini: {$queued}";
        }

        return $lines ? ['title' => 'Kesehatan Sistem Email', 'lines' => $lines] : null;
    }
}
