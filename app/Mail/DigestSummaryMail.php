<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class DigestSummaryMail extends Mailable
{
    /**
     * @param  list<array{title: string, lines: list<string>}>  $sections
     */
    public function __construct(
        public string $recipientName,
        public string $periodLabel,
        public array $sections,
        public string $homeUrl,
    ) {}

    public function build(): static
    {
        return $this->subject('Ringkasan RuteAman — '.$this->periodLabel)
            ->view('emails.digest-summary')
            ->text('emails.digest-summary-text');
    }
}
