<?php

namespace App\Support;

/**
 * Mengurai daftar alamat email bebas (koma, titik koma, spasi, atau baris
 * baru) menjadi alamat valid yang unik dan daftar yang tidak valid,
 * untuk formulir Kirim Informasi. Murni teks — tanpa akses basis data.
 */
final class EmailAddressList
{
    /** @return array{valid: list<string>, invalid: list<string>} */
    public static function parse(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $valid = [];
        $invalid = [];
        foreach ($parts as $part) {
            $email = strtolower(trim($part));
            if ($email === '') {
                continue;
            }
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[$email] = true;
            } else {
                $invalid[$email] = true;
            }
        }

        return ['valid' => array_keys($valid), 'invalid' => array_keys($invalid)];
    }
}
