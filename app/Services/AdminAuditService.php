<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

final class AdminAuditService
{
    public function write(?User $actor, string $action, string $entity, ?int $id, array $meta = [], ?int $school = null, array $changes = [], ?string $reason = null, string $result = 'success'): void
    {
        app(AuditLogger::class)->record($actor, $action, $entity, $id, $meta, $school, $changes, $reason, $result);
    }

    public function notifyOwners(string $message): void
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return;
        }
        foreach (User::where('role', 'owner')->where('status', 'active')->where('active', true)->get() as $owner) {
            try {
                Mail::raw($message, fn ($m) => $m->to($owner->routeNotificationForMail())->subject('Pemberitahuan admin RuteAman'));
                app(MailLogRecorder::class)->recordOutcome($owner, $owner->routeNotificationForMail(), 'admin.notice', 'Pemberitahuan admin RuteAman', true);
            } catch (\Throwable $e) {/* No message payload is logged; the delivery log stores only a recipient hash. */
                app(MailLogRecorder::class)->recordOutcome($owner, $owner->routeNotificationForMail(), 'admin.notice', 'Pemberitahuan admin RuteAman', false, $e);
            }
        }
    }
}
