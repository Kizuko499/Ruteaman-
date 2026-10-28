<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RecipientVerificationMail extends Mailable
{
    public function __construct(
        public string $recipientName,
        public string $verifyUrl,
    ) {}

    public function build(): static
    {
        return $this->subject('Verifikasi alamat penerima notifikasi — RuteAman')
            ->view('emails.recipient-verification')
            ->text('emails.recipient-verification-text');
    }
}
