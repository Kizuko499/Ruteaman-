<?php

namespace Tests\Unit;

use App\Mail\TestMail;
use Tests\TestCase;

class TestMailTest extends TestCase
{
    public function test_sent_at_is_optional_and_auto_filled(): void
    {
        // Kedua pemanggil lama hanya mengirim nama pengirim; konstruktor
        // tidak boleh lagi melempar ArgumentCountError.
        $mail = new TestMail('kai');
        $this->assertSame('kai', $mail->senderName);
        $this->assertNotSame('', $mail->sentAt);
    }

    public function test_explicit_sent_at_is_kept(): void
    {
        $mail = new TestMail('kai', '01 Jan 2027 08:00');
        $this->assertSame('01 Jan 2027 08:00', $mail->sentAt);
    }
}
