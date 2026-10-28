<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ReportStatusMail extends Mailable
{
    public function __construct(
        public string $typeLabel,
        public string $statusLabel,
        public ?string $note,
        public string $hazardUrl,
    ) {}

    public function build(): static
    {
        return $this->subject('Perubahan status laporan — RuteAman')
            ->view('emails.report-status')
            ->text('emails.report-status-text');
    }
}
