<?php

namespace Tests\Unit;

use App\Mail\BroadcastMail;
use Tests\TestCase;

class BroadcastMailTest extends TestCase
{
    public function test_subject_and_body_are_carried(): void
    {
        $mail = new BroadcastMail('kai', 'Pengumuman jadwal', 'Isi informasi untuk semua.');
        $mail->build();
        $this->assertSame('Pengumuman jadwal', $mail->subject);
        $this->assertSame('Isi informasi untuk semua.', $mail->bodyText);
    }
}
