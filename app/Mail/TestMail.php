<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function __construct(
        public string $senderName,
        public string $sentAt = '',
    ) {
        if ($this->sentAt === '') {
            $this->sentAt = now()->format('d M Y H:i');
        }
    }

    public function build(): static
    {
        return $this->subject('Email uji RuteAman')
            ->view('emails.test')
            ->text('emails.test-text');
    }
}
