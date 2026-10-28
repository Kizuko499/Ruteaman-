<?php

namespace Tests\Unit;

use App\Services\SmtpDiagnostics;
use PHPUnit\Framework\TestCase;

class SmtpEncryptionTest extends TestCase
{
    public function test_explicit_value_always_wins(): void
    {
        $this->assertSame(['value' => 'tls', 'inferred' => false], SmtpDiagnostics::effectiveEncryption('tls', 465));
        $this->assertSame(['value' => 'ssl', 'inferred' => false], SmtpDiagnostics::effectiveEncryption(' SSL ', 587));
    }

    public function test_port_inference_matches_mailer_behaviour(): void
    {
        // Kasus nyata di server: MAIL_ENCRYPTION kosong + port 465 —
        // mailer tetap memakai TLS implisit, jadi diagnostik juga harus.
        $this->assertSame(['value' => 'ssl', 'inferred' => true], SmtpDiagnostics::effectiveEncryption('', 465));
        $this->assertSame(['value' => 'tls', 'inferred' => true], SmtpDiagnostics::effectiveEncryption('', 587));
        $this->assertSame(['value' => '', 'inferred' => false], SmtpDiagnostics::effectiveEncryption('', 25));
        $this->assertSame(['value' => '', 'inferred' => false], SmtpDiagnostics::effectiveEncryption('lainnya', 2525));
    }
}
