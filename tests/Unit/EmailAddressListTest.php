<?php

namespace Tests\Unit;

use App\Support\EmailAddressList;
use PHPUnit\Framework\TestCase;

class EmailAddressListTest extends TestCase
{
    public function test_parses_mixed_separators_dedupes_and_flags_invalid(): void
    {
        $result = EmailAddressList::parse("Satu@Contoh.com, dua@contoh.com\nsatu@contoh.com; bukan-email  tiga@contoh.id");
        $this->assertSame(['satu@contoh.com', 'dua@contoh.com', 'tiga@contoh.id'], $result['valid']);
        $this->assertSame(['bukan-email'], $result['invalid']);
    }

    public function test_empty_input(): void
    {
        $this->assertSame(['valid' => [], 'invalid' => []], EmailAddressList::parse('  ,; '));
    }
}
