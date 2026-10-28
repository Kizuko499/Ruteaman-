<?php
// CLI only. Place beside artisan, never in public_html. No API key printed.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = __DIR__;
if (!is_file($root.'/vendor/autoload.php') || !is_file($root.'/bootstrap/app.php')) {
    fwrite(STDERR, "Taruh file ini di root proyek, satu folder dengan artisan.\n"); exit(1);
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$key = (string)config('ruteaman.routing.api_key', '');
echo 'KEY_TERBACA: '.($key !== '' ? 'YA' : 'TIDAK').PHP_EOL;
if ($key === '') exit(1);
$base = rtrim((string)config('ruteaman.routing.base_url'), '/');
if ($base !== 'https://api.openrouteservice.org') {
    echo "BASE_URL: berbeda dari https://api.openrouteservice.org; periksa konfigurasi.\n"; exit(1);
}
$profile = (string)config('ruteaman.routing.profile', 'foot-walking');
if ($profile === 'driving-motorcycle') $profile = 'driving-car';
if (!in_array($profile, ['foot-walking','cycling-regular','driving-car'], true)) {
    echo "PROFILE: tidak didukung oleh pemeriksaan ini.\n"; exit(1);
}
echo "PROFILE_UJI: $profile\n";
echo "Koordinat uji: dua titik publik sekitar Lasem; bukan lokasi perangkat pengguna.\n";
$payload = ['coordinates'=>[[111.4490,-6.6990],[111.451965,-6.6935544]], 'instructions'=>true, 'language'=>'id'];
foreach (['RUTE_DASAR','RUTE_ALTERNATIF'] as $test) {
    $body = $payload;
    if ($test === 'RUTE_ALTERNATIF') $body['alternative_routes'] = [
        'target_count'=>(int)config('ruteaman.routing.alternatives',3),'share_factor'=>.6,'weight_factor'=>1.4,
    ];
    echo "\n$test\n";
    try {
        $r = Illuminate\Support\Facades\Http::withHeaders(['Authorization'=>$key,'Accept'=>'application/geo+json'])
            ->connectTimeout(10)->timeout(25)->post($base.'/v2/directions/'.$profile.'/geojson', $body);
        echo 'HTTP_STATUS: '.$r->status().PHP_EOL;
        $data = $r->json();
        if ($r->successful()) {
            $features = is_array($data) && is_array($data['features'] ?? null) ? $data['features'] : [];
            echo 'JUMLAH_RUTE: '.count($features).PHP_EOL;
            echo 'GEOMETRI: '.($features[0]['geometry']['type'] ?? 'tidak ada').PHP_EOL;
            echo 'JUMLAH_TITIK: '.count($features[0]['geometry']['coordinates'] ?? []).PHP_EOL;
            if (!$features) { echo "Provider tidak mengembalikan geometri rute.\n"; break; }
        } else {
            $error = is_array($data) ? ($data['error'] ?? null) : null;
            $code = is_array($error) ? ($error['code'] ?? null) : null;
            $message = is_array($error) ? ($error['message'] ?? null) : (is_string($error) ? $error : null);
            if (is_scalar($code)) echo 'PROVIDER_CODE: '.substr((string)$code,0,40).PHP_EOL;
            if (is_string($message)) echo 'PROVIDER_MESSAGE: '.substr(str_replace([$key,"\r","\n"],['[REDACTED]',' ',' '],$message),0,500).PHP_EOL;
            else echo "Tidak ada pesan JSON; respons mentah sengaja tidak dicetak.\n";
            if (in_array($r->status(),[401,403],true)) echo "Periksa key, aktivasi dan izin layanan Directions di dashboard provider.\n";
            if ($r->status() === 429) echo "Periksa kuota/rate limit provider.\n";
            if ($test === 'RUTE_DASAR') break;
        }
    } catch (Throwable $e) {
        echo 'ERROR_TYPE: '.get_class($e).PHP_EOL;
        // Do not dump exception messages, request headers, or config values.
        echo "Request gagal sebelum mendapat respons. Periksa akses HTTPS keluar, DNS, sertifikat CA dan timeout hosting. Jangan menonaktifkan verifikasi SSL.\n";
        break;
    }
}
echo "\nSelesai. File ini tidak mengubah database atau konfigurasi.\n";
