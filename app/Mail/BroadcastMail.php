<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Email informasi bebas dari admin (tab Kirim Informasi). Dikirim per
 * penerima secara langsung dari tab Kirim Informasi dan tercatat sebagai
 * kategori admin.broadcast. Isi murni teks dari admin; dirender aman (di-escape)
 * dengan baris baru sebagai paragraf.
 */
class BroadcastMail extends Mailable
{
    public function __construct(
        public string $senderName,
        public string $subjectLine,
        public string $bodyText,
    ) {}

    public function build(): static
    {
        return $this->subject($this->subjectLine)
            ->view('emails.broadcast')
            ->text('emails.broadcast-text');
    }
}
