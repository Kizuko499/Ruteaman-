<?php

namespace App\Services;

use App\Jobs\DeliverEventMail;
use App\Mail\ReportCommentMail;
use App\Mail\ReportStatusMail;
use App\Mail\ReportSubmittedMail;
use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\HazardComment;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Mengirim email peristiwa laporan kepada penerima yang BERHAK saja:
 * akun aktif + email terverifikasi + cakupan sekolah sesuai + preferensi
 * kategori menyala. Setiap pengiriman tercatat di email_delivery_logs
 * dengan kunci dedupe agar peristiwa berulang tidak menggandakan email.
 */
final class ReportMailNotifier
{
    public function __construct(
        private MailLogRecorder $recorder,
        private PermissionService $permissions,
    ) {}

    /** Laporan baru: beri tahu verifikator/admin sekolah yang berwenang atas sekolah laporan itu. */
    public function submitted(Hazard $hazard): void
    {
        $schools = app(HazardSchoolContext::class)->reviewSchools($hazard);
        try {
            app(RecipientNotifier::class)->reportSubmitted($hazard, $schools);
        } catch (\Throwable) {
            // Penerima mandiri tidak boleh menggagalkan notifikasi akun internal.
        }
        if ($schools->isEmpty()) {
            return;
        }
        $reporterIds = $hazard->reports()->whereNotNull('reporter_id')->pluck('reporter_id')->all();
        $candidates = User::whereIn('school_id', $schools->pluck('id')->all())
            ->where('active', true)
            ->whereNotNull('email_verified_at')
            ->whereNotIn('id', $reporterIds ?: [0])
            ->get();
        foreach ($candidates as $user) {
            $school = $schools->firstWhere('id', $user->school_id);
            if (! $school || ! $this->permissions->can($user, 'verification.decide', $school, true)) {
                continue;
            }
            $this->dispatch(
                $user,
                'report.submitted',
                'report_new_in_scope',
                'Laporan baru menunggu verifikasi — RuteAman',
                ReportSubmittedMail::class,
                [$hazard->type?->label ?? 'Kondisi jalan', $school->name, $hazard->location_label, route('verifier.queue')],
                'submitted:'.$hazard->id.':'.$user->id,
            );
        }
    }

    /** Perubahan status dari catatan audit: beri tahu para pelapor hazard itu. */
    public function statusFromAudit(AuditLog $log): void
    {
        $labels = AccountNotificationService::LABELS;
        if (! isset($labels[$log->action])) {
            return;
        }
        $hazard = Hazard::with('type')->find($log->entity_id);
        if (! $hazard) {
            return;
        }
        $note = null;
        if (in_array($log->action, ['verification.needs_evidence', 'verification.rejected'], true)) {
            $note = $hazard->verifications()
                ->when($log->created_at, fn ($q) => $q->where('created_at', '<=', $log->created_at))
                ->latest('id')->value('note');
        }
        try {
            app(RecipientNotifier::class)->reportStatusChanged($hazard, $labels[$log->action], $note, $log);
        } catch (\Throwable) {
        }
        $reporters = $hazard->reports()->whereNotNull('reporter_id')->with('reporter')->get()
            ->pluck('reporter')->filter()->unique('id');
        foreach ($reporters as $user) {
            $this->dispatch(
                $user,
                'report.status_changed',
                'report_status',
                'Perubahan status laporan — RuteAman',
                ReportStatusMail::class,
                [$hazard->type?->label ?? 'Kondisi jalan', $labels[$log->action], $note, route('hazards.public', $hazard)],
                'audit:'.$log->id.':'.$user->id,
            );
        }
    }

    /** Komentar baru: beri tahu pelapor dan peserta komentar sebelumnya, kecuali penulisnya. */
    public function commentAdded(HazardComment $comment): void
    {
        $hazard = $comment->hazard()->with('type')->first();
        if (! $hazard) {
            return;
        }
        $ids = $hazard->reports()->whereNotNull('reporter_id')->pluck('reporter_id')
            ->merge($hazard->comments()->whereNotNull('user_id')->pluck('user_id'))
            ->unique()
            ->reject(fn ($id) => $id === $comment->user_id)
            ->all();
        if (! $ids) {
            return;
        }
        $users = User::whereIn('id', $ids)->where('active', true)->whereNotNull('email_verified_at')->get();
        foreach ($users as $user) {
            $this->dispatch(
                $user,
                'report.comment_added',
                'report_comments',
                'Komentar baru pada laporan — RuteAman',
                ReportCommentMail::class,
                [$hazard->type?->label ?? 'Kondisi jalan', $comment->authorName(), Str::limit(trim((string) $comment->body), 140), route('hazards.public', $hazard)],
                'comment:'.$comment->id.':'.$user->id,
            );
        }
        try {
            app(RecipientNotifier::class)->reportCommentAdded($hazard, $comment);
        } catch (\Throwable) {
        }
    }

    private function dispatch(User $user, string $category, string $prefKey, string $subject, string $mailableClass, array $args, string $dedupeKey): void
    {
        if (! $user->active || ! $user->email_verified_at || ! $user->wantsMail($prefKey)) {
            return;
        }
        $email = $user->routeNotificationForMail();
        $log = $this->recorder->startOnce($user, $email, $category, $subject, $dedupeKey, ['mailable' => $mailableClass, 'args' => $args]);
        if (! $log) {
            return;
        }
        if ($log->status === 'failed') {
            $log->forceFill(['status' => 'queued', 'queued_at' => now(), 'error' => null, 'failed_at' => null])->save();
        }
        DeliverEventMail::dispatch($log->id, $email, $mailableClass, $args)->afterCommit();
    }
}
