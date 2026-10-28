<?php

namespace App\Services;

use App\Jobs\DeliverEventMail;
use App\Mail\ReportCommentMail;
use App\Mail\ReportStatusMail;
use App\Mail\ReportSubmittedMail;
use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\HazardComment;
use App\Models\NotificationRecipient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mengirim email peristiwa laporan kepada PENERIMA MANDIRI (NotificationRecipient)
 * yang aktif, terverifikasi, ber-mode realtime/both, berlangganan kategorinya,
 * dan berada dalam cakupan hazard. Dipanggil ReportMailNotifier setelah alur
 * akun internal berjalan; kegagalan di sini tidak pernah menggagalkan alur utama.
 *
 * Jam tenang: kategori kritis (laporan baru, perubahan status) tidak tertahan;
 * kategori nonkritis (komentar) digeser ke akhir jam tenang penerima.
 */
final class RecipientNotifier
{
    public function __construct(
        private MailLogRecorder $recorder,
        private DigestScheduleCalculator $calculator,
    ) {}

    public function reportSubmitted(Hazard $hazard, Collection $schools): void
    {
        foreach ($this->recipientsFor('report.submitted', $schools->pluck('id')->all()) as $recipient) {
            $school = $schools->firstWhere('id', $recipient->school_id);
            $this->send(
                $recipient,
                'report.submitted',
                'Laporan baru menunggu verifikasi — RuteAman',
                ReportSubmittedMail::class,
                [$hazard->type?->label ?? 'Kondisi jalan', $school?->name ?? 'RuteAman', $hazard->location_label, route('hazards.public', $hazard)],
                'submitted:'.$hazard->id.':r'.$recipient->id,
                critical: true,
            );
        }
    }

    public function reportStatusChanged(Hazard $hazard, string $statusLabel, ?string $note, AuditLog $log): void
    {
        $schoolIds = app(HazardSchoolContext::class)->reviewSchools($hazard)->pluck('id')->all();
        foreach ($this->recipientsFor('report.status_changed', $schoolIds) as $recipient) {
            $this->send(
                $recipient,
                'report.status_changed',
                'Perubahan status laporan — RuteAman',
                ReportStatusMail::class,
                [$hazard->type?->label ?? 'Kondisi jalan', $statusLabel, $note, route('hazards.public', $hazard)],
                'audit:'.$log->id.':r'.$recipient->id,
                critical: true,
            );
        }
    }

    public function reportCommentAdded(Hazard $hazard, HazardComment $comment): void
    {
        $schoolIds = app(HazardSchoolContext::class)->reviewSchools($hazard)->pluck('id')->all();
        foreach ($this->recipientsFor('report.comment_added', $schoolIds) as $recipient) {
            $this->send(
                $recipient,
                'report.comment_added',
                'Komentar baru pada laporan — RuteAman',
                ReportCommentMail::class,
                [$hazard->type?->label ?? 'Kondisi jalan', $comment->authorName(), Str::limit(trim((string) $comment->body), 140), route('hazards.public', $hazard)],
                'comment:'.$comment->id.':r'.$recipient->id,
                critical: false,
            );
        }
    }

    /** @return Collection<int, NotificationRecipient> */
    private function recipientsFor(string $category, array $hazardSchoolIds): Collection
    {
        if (! Schema::hasTable('notification_recipients')) {
            return collect();
        }

        $query = NotificationRecipient::deliverable()->whereIn('mode', ['realtime', 'both']);
        if ($category === 'report.submitted') {
            $query->where(fn ($q) => $q->whereHas('categoryRows', fn ($c) => $c->where('category', $category))->orWhere('receive_all_reports', true));
        } else {
            $query->whereHas('categoryRows', fn ($q) => $q->where('category', $category));
        }

        return $query->get()
            ->filter(fn (NotificationRecipient $r) => $r->school_id === null || in_array($r->school_id, $hazardSchoolIds, true))
            ->values();
    }

    private function send(NotificationRecipient $recipient, string $category, string $subject, string $mailableClass, array $args, string $dedupeKey, bool $critical): void
    {
        $log = $this->recorder->startOnce(null, $recipient->email, $category, $subject, $dedupeKey, ['mailable' => $mailableClass, 'args' => $args], $recipient->id);
        if (! $log) {
            return;
        }
        if ($log->status === 'failed') {
            $log->forceFill(['status' => 'queued', 'queued_at' => now(), 'error' => null, 'failed_at' => null])->save();
        }
        $job = DeliverEventMail::dispatch($log->id, $recipient->email, $mailableClass, $args)->afterCommit();
        if (! $critical && $this->calculator->inQuietHours($recipient, Carbon::now('UTC'))) {
            $job->delay($this->calculator->quietEndAfter($recipient, Carbon::now('UTC')));
        }
    }
}
