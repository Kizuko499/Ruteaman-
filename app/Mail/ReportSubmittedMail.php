<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ReportSubmittedMail extends Mailable
{
    public function __construct(
        public string $typeLabel,
        public string $schoolName,
        public ?string $locationLabel,
        public string $queueUrl,
    ) {}

    public function build(): static
    {
        return $this->subject('Laporan baru menunggu verifikasi — RuteAman')
            ->view('emails.report-submitted')
            ->text('emails.report-submitted-text');
    }
}
