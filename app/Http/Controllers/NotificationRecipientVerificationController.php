<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use Illuminate\Http\Request;

/**
 * Verifikasi alamat penerima notifikasi mandiri. Tautan dibuat dengan
 * URL::temporarySignedRoute (berlaku 72 jam) DAN membawa token acak yang
 * hash-nya tersimpan di baris penerima — keduanya harus cocok.
 */
class NotificationRecipientVerificationController extends Controller
{
    public function __invoke(Request $request, NotificationRecipient $recipient)
    {
        $token = (string) $request->query('token', '');
        $valid = $recipient->verification_token_hash
            && hash_equals($recipient->verification_token_hash, hash('sha256', $token));
        if (! $valid) {
            return response()->view('notification-recipient-verified', ['state' => 'invalid', 'recipient' => $recipient], 403);
        }
        if ($recipient->verification_sent_at && $recipient->verification_sent_at->copy()->addHours(72)->isPast()) {
            return response()->view('notification-recipient-verified', ['state' => 'expired', 'recipient' => $recipient], 410);
        }
        if (! $recipient->verified_at) {
            $recipient->forceFill(['verified_at' => now(), 'verification_token_hash' => null])->save();
        }

        return view('notification-recipient-verified', ['state' => 'ok', 'recipient' => $recipient]);
    }
}
