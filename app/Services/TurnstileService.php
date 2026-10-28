<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class TurnstileService {
    public static function verify(?string $token, ?string $ip = null): bool {
        $secret = config('services.turnstile.secret');
        // Jika belum dikonfigurasi, lewati (jangan block)
        if (!$secret || !$token) return !$secret;
        try {
            $res = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);
            return (bool) ($res->json('success') ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }
    public static function siteKey(): ?string {
        return config('services.turnstile.site_key');
    }
    public static function enabled(): bool {
        return (bool) config('services.turnstile.site_key');
    }
}
