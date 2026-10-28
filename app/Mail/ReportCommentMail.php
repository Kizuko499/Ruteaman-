<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ReportCommentMail extends Mailable
{
    public function __construct(
        public string $typeLabel,
        public string $commenterName,
        public string $excerpt,
        public string $hazardUrl,
    ) {}

    public function build(): static
    {
        return $this->subject('Komentar baru pada laporan — RuteAman')
            ->view('emails.report-comment')
            ->text('emails.report-comment-text');
    }
}
