<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Uji koneksi SMTP tanpa mengirim email: membuka soket ke host dari konfigurasi,
 * membaca sapaan server, melakukan EHLO, dan (untuk TLS) memverifikasi rantai
 * sertifikat dengan verifikasi peer AKTIF — persis seperti mailer. Nama pada
 * sertifikat (CN/SAN) dan masa berlakunya dilaporkan agar ketidakcocokan
 * hostname terlihat jelas. Kata sandi tidak pernah dibaca apalagi dicatat.
 */
final class SmtpDiagnostics
{
    public const CACHE_KEY = 'smtp_diag_last';

    /**
     * Enkripsi efektif, mengikuti aturan mailer (Symfony): nilai eksplisit
     * 'ssl'/'tls' selalu menang; tanpa nilai eksplisit, port yang menentukan
     * (465 = TLS implisit, 587 = STARTTLS).
     *
     * @return array{value: string, inferred: bool}
     */
    public static function effectiveEncryption(string $raw, int $port): array
    {
        $value = strtolower(trim($raw));
        if ($value === 'ssl' || $value === 'tls') {
            return ['value' => $value, 'inferred' => false];
        }

        return match ($port) {
            465 => ['value' => 'ssl', 'inferred' => true],
            587 => ['value' => 'tls', 'inferred' => true],
            default => ['value' => '', 'inferred' => false],
        };
    }

    /** @return array<string, mixed> */
    public function check(): array
    {
        $host = (string) config('mail.mailers.smtp.host', '');
        $port = (int) config('mail.mailers.smtp.port', 0);
        $effective = self::effectiveEncryption((string) (config('mail.mailers.smtp.encryption') ?? ''), $port);
        $encryption = $effective['value'];
        $result = [
            'ok' => false,
            'stage' => 'konfigurasi',
            'message' => '',
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption === '' ? '(tidak ada)' : $encryption.($effective['inferred'] ? ' (tersirat dari port)' : ''),
            'cert_subject' => null,
            'cert_sans' => null,
            'cert_expires' => null,
            'checked_at' => now()->toIso8601String(),
        ];

        try {
            if ($host === '' || $port === 0) {
                $result['message'] = 'Host atau port SMTP belum terisi di konfigurasi server.';

                return $this->store($result);
            }
            $context = stream_context_create(['ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'capture_peer_cert' => true,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ]]);
            $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
            $errno = 0;
            $errstr = '';
            $fp = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $context);
            if ($fp === false) {
                $result['stage'] = 'koneksi/tls';
                $result['message'] = trim($errstr) !== '' ? $errstr : 'Koneksi ditolak server.';

                return $this->store($result);
            }
            stream_set_timeout($fp, 12);
            $this->readCert($fp, $result);
            [$greetCode, $greeting] = $this->readReply($fp);
            if ($greetCode !== '220') {
                $result['stage'] = 'sapaan server';
                $result['message'] = trim($greeting) === ''
                    ? 'Server tidak mengirim sapaan sama sekali (koneksi diterima tetapi diam). Biasanya ini tanda port dan enkripsi tidak cocok.'
                    : 'Sapaan server tidak wajar: '.mb_substr(trim(str_replace(["\r\n", "\r", "\n"], ' ', $greeting)), 0, 120);
                fclose($fp);

                return $this->store($result);
            }
            if ($encryption === 'tls') {
                fwrite($fp, "EHLO ruteaman.my.id\r\n");
                $this->readReply($fp);
                fwrite($fp, "STARTTLS\r\n");
                [$startCode, $startText] = $this->readReply($fp);
                if ($startCode !== '220') {
                    $result['stage'] = 'starttls';
                    $result['message'] = 'Server menolak STARTTLS: '.mb_substr(trim(str_replace(["\r\n", "\r", "\n"], ' ', $startText)), 0, 120);
                    fclose($fp);

                    return $this->store($result);
                }
                if (@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT, $fp) !== true) {
                    $result['stage'] = 'tls';
                    $result['message'] = 'Jabat tangan TLS setelah STARTTLS gagal atau sertifikat tidak cocok dengan hostname.';
                    fclose($fp);

                    return $this->store($result);
                }
                $this->readCert($fp, $result);
            }
            fwrite($fp, "EHLO ruteaman.my.id\r\n");
            [$ehloCode, $ehlo] = $this->readReply($fp);
            if ($ehloCode !== '250' && str_starts_with($ehloCode, '5')) {
                // Server lama yang menolak EHLO masih bisa diajak bicara via HELO.
                fwrite($fp, "HELO ruteaman.my.id\r\n");
                [$ehloCode, $ehlo] = $this->readReply($fp);
            }
            fwrite($fp, "QUIT\r\n");
            fclose($fp);
            if ($ehloCode !== '250') {
                $result['stage'] = 'ehlo';
                $result['message'] = 'Server tidak menjawab EHLO dengan benar: '.mb_substr(trim(str_replace(["\r\n", "\r", "\n"], ' ', $ehlo)), 0, 120);

                return $this->store($result);
            }
            $result['ok'] = true;
            $result['stage'] = 'selesai';
            $result['message'] = 'Koneksi berhasil, EHLO diterima, dan sertifikat lolos verifikasi untuk hostname ini.';

            return $this->store($result);
        } catch (\Throwable $e) {
            $result['message'] = MailLogRecorder::sanitizeError($e);

            return $this->store($result);
        }
    }

    private function readCert($fp, array &$result): void
    {
        try {
            $params = stream_context_get_params($fp);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            if ($cert) {
                $parsed = openssl_x509_parse($cert);
                if (is_array($parsed)) {
                    $result['cert_subject'] = $parsed['subject']['CN'] ?? null;
                    $san = $parsed['extensions']['subjectAltName'] ?? null;
                    $result['cert_sans'] = $san ? mb_substr($san, 0, 300) : null;
                    $result['cert_expires'] = isset($parsed['validTo_time_t']) ? date('d M Y', $parsed['validTo_time_t']) : null;
                }
            }
        } catch (\Throwable) {
        }
    }

    /**
     * Membaca satu balasan SMTP utuh, termasuk balasan multi-baris
     * (baris lanjutan berkode "220-…" sampai baris penutup "220 …").
     * Sapaan server cPanel/Exim umumnya multi-baris; membaca satu baris
     * saja akan menyisakan sisa sapaan yang terbaca sebagai jawaban EHLO.
     *
     * @return array{string, string} [kode 3 digit ('' bila tidak terbaca), teks utuh]
     */
    private function readReply($fp): array
    {
        $out = '';
        $code = '';
        while (($line = fgets($fp, 512)) !== false) {
            $out .= $line;
            if (preg_match('/^(\d{3})([ -]|$)/', $line, $m)) {
                $code = $m[1];
                if ($m[2] !== '-') {
                    break;
                }
            }
            if (strlen($out) > 8192) {
                break;
            }
        }

        return [$code, $out];
    }

    private function store(array $result): array
    {
        Cache::forever(self::CACHE_KEY, $result);

        return $result;
    }

    public static function last(): ?array
    {
        $value = Cache::get(self::CACHE_KEY);

        return is_array($value) ? $value : null;
    }
}
