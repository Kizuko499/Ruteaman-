<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DapodikSchoolDirectory
{
    private string $baseUrl;

    private string $fallbackUrl;

    private string $regionUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (config('services.dapodik.base_url') ?: 'https://dapo.kemendikdasmen.go.id'), '/');
        $this->fallbackUrl = rtrim((string) (config('services.dapodik.fallback_url') ?: 'https://sekolah.devapi.id'), '/');
        $this->regionUrl = rtrim((string) (config('services.dapodik.region_url') ?: 'https://wilayah.id/api'), '/');
    }

    public function provinces(): array
    {
        return Cache::remember('school.directory.online-provinces.v1', now()->addDay(), function () {
            try {
                return collect($this->regionRequest('/provinces.json'))
                    ->map(fn (array $item) => ['code' => (string) $item['code'], 'name' => trim((string) $item['name'])])
                    ->values()->all();
            } catch (\Throwable $error) {
                report($error);

                return collect($this->regions()['provinces'])
                    ->map(fn (array $item) => ['code' => $item['code'], 'name' => $item['name']])
                    ->values()->all();
            }
        });
    }

    public function regencies(string $provinceCode): array
    {
        return Cache::remember("school.directory.online-regencies.v1.{$provinceCode}", now()->addDay(), function () use ($provinceCode) {
            try {
                return collect($this->regionRequest("/regencies/{$provinceCode}.json"))
                    ->map(fn (array $item) => [
                        'code' => (string) $item['code'],
                        'province_code' => $provinceCode,
                        'name' => trim((string) $item['name']),
                    ])->values()->all();
            } catch (\Throwable $error) {
                report($error);
                $province = collect($this->regions()['provinces'])->firstWhere('code', $provinceCode);

                return collect($province['regencies'] ?? [])->map(fn (array $item) => [
                    'code' => $item['code'],
                    'province_code' => $provinceCode,
                    'name' => $item['name'],
                ])->values()->all();
            }
        });
    }

    public function districts(string $provinceCode, string $regencyCode): array
    {
        return Cache::remember("school.directory.online-districts.v1.{$regencyCode}", now()->addDay(), function () use ($provinceCode, $regencyCode) {
            try {
                return collect($this->regionRequest("/districts/{$regencyCode}.json"))
                    ->map(fn (array $item) => [
                        'code' => (string) $item['code'],
                        'regency_code' => $regencyCode,
                        'name' => trim((string) $item['name']),
                    ])->values()->all();
            } catch (\Throwable $error) {
                report($error);
                $province = collect($this->regions()['provinces'])->firstWhere('code', $provinceCode);
                $regency = collect($province['regencies'] ?? [])->firstWhere('code', $regencyCode);

                return collect($regency['districts'] ?? [])->map(fn (array $item) => [
                    'code' => $item['code'],
                    'regency_code' => $regencyCode,
                    'name' => $item['name'],
                ])->values()->all();
            }
        });
    }

    public function search(string $query, string $provinceCode, string $regencyCode, string $districtCode): array
    {
        $query = trim($query);
        $regions = collect($this->regencies($provinceCode));
        $regency = $regions->firstWhere('code', $regencyCode);
        $province = collect($this->provinces())->firstWhere('code', $provinceCode);
        $district = collect($this->districts($provinceCode, $regencyCode))->firstWhere('code', $districtCode);

        if (! $province || ! $regency || ! $district) {
            throw new RuntimeException('Wilayah sekolah tidak valid.');
        }

        // v2 invalidates earlier cached empty Dapodik results, including madrasah.
        $key = 'school.directory.search.v2.'.sha1("{$query}|{$provinceCode}|{$regencyCode}|{$districtCode}");
        if ($cached = Cache::get($key)) {
            return $cached;
        }
        $matches = fn (array $items) => collect($items)
            ->filter(fn (array $item) => ! empty($item['npsn']) && ! empty($item['name'])
                && (! preg_match('/^[A-Za-z0-9]{8}$/', $query) || strtoupper($item['npsn']) === strtoupper($query))
                && $this->sameRegion($item['province'] ?? '', $province['name'])
                && $this->sameRegion($item['regency'] ?? '', $regency['name'])
                && $this->sameRegion($item['district'] ?? '', $district['name']))
            ->unique('npsn')->take(30)
            ->map(fn (array $item) => collect($item)->only(['npsn', 'name', 'level', 'status', 'province', 'regency', 'district'])->all())
            ->values()->all();

        $items = [];
        try {
            $items = $matches(collect($this->dapodikRequest('/api/detail-sekolah/search', ['q' => $query]))
                ->map(fn (array $item) => $this->normalizeDapodikSearch($item))->all());
        } catch (\Throwable $primaryError) {
            report($primaryError);
        }
        // An HTTP 200 with data=[] is NOT an outage, but still needs the alternate directory.
        // Also retry the alternate source if Dapodik records lack the selected region.
        if ($items === []) {
            $fallbackQuery = preg_match('/^[A-Za-z0-9]{8}$/', $query)
                ? ['npsn' => strtoupper($query), 'limit' => 1]
                : ['nama' => $query, 'limit' => 50];
            $items = $matches(collect($this->fallbackRequest('/sekolah', $fallbackQuery))
                ->map(fn (array $item) => $this->normalizeFallback($item))->all());
        }
        // Do not retain empty results: a temporary upstream gap must not hide a school for 15 minutes.
        if ($items !== []) {
            Cache::put($key, $items, now()->addMinutes(15));
        }

        return $items;
    }

    private function detailRecord(string $npsn): ?array
    {
        return Cache::remember(
            "school.directory.detail.v2.{$npsn}",
            now()->addHours(12),
            function () use ($npsn) {
                $record = null;
                try {
                    $records = $this->dapodikRequest('/api/detail-sekolah', ['npsn' => $npsn]);
                    $latest = collect($records)->filter(fn (array $r) => strtoupper((string) ($r['npsn'] ?? '')) === strtoupper($npsn))
                        ->sortByDesc('tanggal_update')->first();
                    $record = $latest ? $this->normalizeDapodikDetail($latest) : null;
                } catch (\Throwable $primaryError) {
                    report($primaryError);
                }
                if (! $record) {
                    $fallback = collect($this->fallbackRequest('/sekolah', ['npsn' => $npsn, 'limit' => 1]))
                        ->first(fn (array $r) => strtoupper((string) ($r['npsn'] ?? '')) === strtoupper($npsn));
                    $record = $fallback ? $this->normalizeFallback($fallback) : null;
                }

                return $record;
            }
        );
    }

    /**
     * Impor sekolah hanya dari NPSN (alur pencarian nasional): kode wilayah
     * diturunkan dari nama wilayah pada catatan direktori itu sendiri,
     * lalu diverifikasi penuh oleh import().
     */
    public function importNationwide(string $npsn): School
    {
        $record = $this->detailRecord($npsn);
        if (! $record) {
            throw new RuntimeException('Sekolah tidak ditemukan pada direktori nasional.');
        }
        $province = collect($this->provinces())->first(fn (array $item) => $this->sameRegion($record['province'] ?? '', $item['name'] ?? ''));
        $regency = $province ? collect($this->regencies($province['code']))->first(fn (array $item) => $this->sameRegion($record['regency'] ?? '', $item['name'] ?? '')) : null;
        $district = ($province && $regency) ? collect($this->districts($province['code'], $regency['code']))->first(fn (array $item) => $this->sameRegion($record['district'] ?? '', $item['name'] ?? '')) : null;
        if (! $province || ! $regency || ! $district) {
            throw new RuntimeException('Wilayah sekolah tidak dapat dikenali dari direktori nasional.');
        }

        return $this->import($npsn, $province['code'], $regency['code'], $district['code']);
    }

    public function import(string $npsn, string $provinceCode, string $regencyCode, string $districtCode): School
    {
        $record = $this->detailRecord($npsn);
        $province = collect($this->provinces())->firstWhere('code', $provinceCode);
        $regency = collect($this->regencies($provinceCode))->firstWhere('code', $regencyCode);
        $district = collect($this->districts($provinceCode, $regencyCode))->firstWhere('code', $districtCode);

        if (! $record || ! $province || ! $regency || ! $district
            || ! $this->sameRegion($record['province'] ?? '', $province['name'])
            || ! $this->sameRegion($record['regency'] ?? '', $regency['name'])
            || ! $this->sameRegion($record['district'] ?? '', $district['name'])) {
            throw new RuntimeException('Sekolah tidak cocok dengan provinsi, kabupaten/kota, dan kecamatan yang dipilih.');
        }

        $latitude = filter_var($record['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($record['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false || abs($latitude) > 90 || abs($longitude) > 180) {
            throw new RuntimeException('Koordinat sekolah belum tersedia pada direktori pendidikan.');
        }

        $attributes = [
            'name' => (string) $record['name'],
            'npsn' => (string) $record['npsn'],
            'education_level' => (string) ($record['level'] ?? ''),
            'school_status' => (string) ($record['status'] ?? ''),
            'province_code' => $provinceCode,
            'province_name' => $province['name'],
            'regency_code' => $regencyCode,
            'regency_name' => $regency['name'],
            'district_code' => $districtCode,
            'district_name' => $district['name'],
            'address' => (string) ($record['address'] ?? ''),
            'directory_synced_at' => now(),
            'timezone' => $longitude >= 130 ? 'Asia/Jayapura' : ($longitude >= 112 ? 'Asia/Makassar' : 'Asia/Jakarta'),
        ];

        if ($school = School::query()->where('npsn', $npsn)->first()) {
            $school->update($attributes);

            return $school->refresh();
        }

        $delta = 0.025;
        $polygon = sprintf(
            'POLYGON((%1$f %2$f,%3$f %2$f,%3$f %4$f,%1$f %4$f,%1$f %2$f))',
            $longitude - $delta,
            $latitude - $delta,
            $longitude + $delta,
            $latitude + $delta
        );

        $columns = array_keys($attributes);
        $values = array_values($attributes);
        $quotedColumns = implode(',', array_map(fn ($column) => DB::getDriverName() === 'pgsql' ? "\"{$column}\"" : "`{$column}`", $columns));
        $placeholders = implode(',', array_fill(0, count($columns), '?'));

        if (DB::getDriverName() === 'pgsql') {
            DB::insert(
                "INSERT INTO schools ({$quotedColumns}, location, catchment) VALUES ({$placeholders}, ST_SetSRID(ST_MakePoint(?,?),4326), ST_GeomFromText(?,4326))",
                [...$values, $longitude, $latitude, $polygon]
            );
        } else {
            DB::insert(
                "INSERT INTO schools ({$quotedColumns}, location, catchment) VALUES ({$placeholders}, ".SpatialSql::wkt().', '.SpatialSql::wkt().')',
                [...$values, "POINT({$longitude} {$latitude})", $polygon]
            );
        }

        return School::query()->where('npsn', $npsn)->firstOrFail();
    }

    /** Search both online sources nationwide; never restrict to local registrations. */
    public function searchNationwide(string $query): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        if (mb_strlen($query) < 3 || mb_strlen($query) > 80) {
            throw new RuntimeException('Ketik nama sekolah minimal 3 karakter atau NPSN.');
        }
        // Alphabetic names of length eight (e.g. Sukamaju) are NOT NPSNs.
        $isNpsn = (bool) preg_match('/^(?:[0-9]{8}|[A-Za-z][0-9]{7})$/', $query);
        $key = 'school.directory.nationwide.v4.'.sha1(mb_strtolower($query));
        if ($cached = Cache::get($key)) {
            return $cached;
        }
        $canonical = fn (string $value) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', preg_replace('/^(SD|SMP|SMA|SMK|MI|MTS|MA)N\b/i', '$1 NEGERI', trim($value)))));
        // Cek database lokal dulu (cache progresif) — cepat & tidak tergantung API eksternal
        $localResults = $this->searchLocalCache($query, $isNpsn, $canonical);
        if ($localResults !== []) {
            return $localResults;
        }
        $queries = [$query];
        if (! $isNpsn) {
            $expanded = preg_replace('/^(SD|SMP|SMA|SMK|MI|MTS|MA)N\b/i', '$1 NEGERI', $query);
            $shortened = preg_replace('/^(SD|SMP|SMA|SMK|MI|MTS|MA)\s+NEGERI\b/i', '$1N', $query);
            $queries = array_values(array_unique([$query, $expanded, $shortened]));
        }
        $items = [];
        $successfulSources = 0;
        foreach ($queries as $term) {
            foreach (['dapodik', 'fallback'] as $source) {
                try {
                    $raw = $source === 'dapodik'
                        ? $this->dapodikRequest('/api/detail-sekolah/search', ['q' => $term])
                        : $this->fallbackRequest('/sekolah', $isNpsn ? ['npsn' => strtoupper($query), 'limit' => 1] : ['nama' => $term, 'limit' => 50]);
                    $successfulSources++;
                    foreach ($raw as $record) {
                        if (! is_array($record)) {
                            continue;
                        }
                        $item = $source === 'dapodik' ? $this->normalizeDapodikSearch($record) : $this->normalizeFallback($record);
                        if (! preg_match('/^[A-Za-z0-9]{8}$/', $item['npsn']) || $item['name'] === '') {
                            continue;
                        }
                        $matches = $isNpsn ? strtoupper($item['npsn']) === strtoupper($query) : mb_stripos($canonical($item['name']), $canonical($query)) !== false;
                        if ($matches) {
                            $items[] = collect($item)->only(['npsn', 'name', 'level', 'status', 'province', 'regency', 'district'])->all();
                        }
                    }
                } catch (\Throwable $error) {
                    // Do not log request headers, public API tokens or HTTP bodies.
                    logger()->warning('Direktori sekolah online gagal.', ['source' => $source, 'error_type' => get_class($error)]);
                }
            }
            if ($items !== []) {
                break;
            }
        }
        if ($successfulSources === 0) {
            throw new RuntimeException('Kedua sumber sekolah online belum dapat dihubungi. Periksa akses HTTPS keluar dari hosting.');
        }
        $items = collect($items)->unique(fn ($item) => strtoupper($item['npsn']))->take(30)->values()->all();
        if ($items !== []) {
            Cache::put($key, $items, now()->addMinutes(15));
            // Simpan ke database lokal agar pencarian berikutnya instant
            $this->saveToLocalCache($items);
        }

        return $items;
    }

    /**
     * Cari di cache database lokal terlebih dahulu.
     * Database ini terbangun secara progresif dari hasil API yang berhasil.
     */
    private function searchLocalCache(string $query, bool $isNpsn, callable $canonical): array
    {
        try {
            $db = DB::table('school_catalog_cache');
            if ($isNpsn) {
                $record = $db->where('npsn', strtoupper(trim($query)))->first();
                return $record ? [$this->formatCachedRecord($record)] : [];
            }
            $normalized = $canonical($query);
            // Cari berdasarkan nama yang dinormalisasi (LIKE untuk partial match)
            $records = $db->where('name_normalized', 'like', '%' . addcslashes($normalized, '%_\\') . '%')
                ->orderBy('name')
                ->limit(20)
                ->get();
            return $records->map(fn ($r) => $this->formatCachedRecord($r))->all();
        } catch (\Throwable $e) {
            // Tabel belum ada (migration belum jalan) — fallback ke API
            return [];
        }
    }

    private function formatCachedRecord(object $record): array
    {
        return [
            'npsn' => (string) $record->npsn,
            'name' => (string) $record->name,
            'level' => $record->level,
            'status' => $record->status,
            'province' => $record->province,
            'regency' => $record->regency,
            'district' => $record->district,
        ];
    }

    /**
     * Simpan hasil API ke cache database lokal (upsert — aman dijalankan berulang).
     */
    private function saveToLocalCache(array $items): void
    {
        try {
            foreach ($items as $item) {
                if (empty($item['npsn']) || empty($item['name'])) {
                    continue;
                }
                $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $item['name'])));
                DB::table('school_catalog_cache')->upsert(
                    [[
                        'npsn' => strtoupper(trim((string) $item['npsn'])),
                        'name' => trim((string) $item['name']),
                        'name_normalized' => $normalized,
                        'level' => $item['level'] ?? null,
                        'status' => $item['status'] ?? null,
                        'province' => $item['province'] ?? null,
                        'regency' => $item['regency'] ?? null,
                        'district' => $item['district'] ?? null,
                        'source' => 'dapodik',
                        'source_updated_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]],
                    ['npsn'],
                    ['name', 'name_normalized', 'level', 'status', 'province', 'regency', 'district', 'source', 'source_updated_at', 'updated_at']
                );
            }
        } catch (\Throwable $e) {
            // Jangan gagalkan pencarian hanya karena cache gagal
            logger()->warning('Gagal simpan cache katalog sekolah.', ['error' => $e->getMessage()]);
        }
    }

    /** Resolve a verified directory record, without creating a school or account. */
    public function guestPreview(string $npsn): array
    {
        $npsn = strtoupper($npsn);
        $key = "school.directory.detail.v2.{$npsn}";
        $record = Cache::get($key);
        if (! $record) {
            try {
                $latest = collect($this->dapodikRequest('/api/detail-sekolah', ['npsn' => $npsn]))
                    ->filter(fn ($s) => strtoupper((string) ($s['npsn'] ?? '')) === $npsn)
                    ->sortByDesc('tanggal_update')->first();
                $record = $latest ? $this->normalizeDapodikDetail($latest) : null;
            } catch (\Throwable $error) {
                report($error);
            }
            if (! $record) {
                $item = collect($this->fallbackRequest('/sekolah', ['npsn' => $npsn, 'limit' => 1]))
                    ->first(fn ($s) => strtoupper((string) ($s['npsn'] ?? '')) === $npsn);
                $record = $item ? $this->normalizeFallback($item) : null;
            }
        }
        $lat = filter_var($record['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($record['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        if (! $record || empty($record['name']) || $lat === false || $lng === false
            || ! is_finite($lat) || ! is_finite($lng) || $lat < -11.5 || $lat > 6.5 || $lng < 94 || $lng > 142.5) {
            throw new RuntimeException('Data atau koordinat sekolah belum tersedia. Pilih sekolah lain atau hubungi pengelola.');
        }
        $province = collect($this->provinces())->first(fn ($s) => $this->sameRegion($record['province'] ?? '', $s['name']));
        $regency = $province ? collect($this->regencies($province['code']))
            ->first(fn ($s) => $this->sameRegion($record['regency'] ?? '', $s['name'])) : null;
        $district = $regency ? collect($this->districts($province['code'], $regency['code']))
            ->first(fn ($s) => $this->sameRegion($record['district'] ?? '', $s['name'])) : null;
        if (! $province || ! $regency || ! $district) {
            throw new RuntimeException('Wilayah sekolah belum dapat dicocokkan. Coba NPSN sekolah atau hubungi pengelola.');
        }
        Cache::put($key, $record, now()->addHours(12));
        $delta = 0.025;

        return [
            'id' => 'directory:'.$npsn, 'npsn' => $npsn, 'name' => $record['name'],
            'province_code' => $province['code'], 'regency_code' => $regency['code'], 'district_code' => $district['code'],
            'province' => $province['name'], 'regency' => $regency['name'], 'district' => $district['name'],
            'latitude' => (float) $lat, 'longitude' => (float) $lng,
            'catchment' => ['type' => 'Polygon', 'coordinates' => [[
                [$lng - $delta, $lat - $delta], [$lng + $delta, $lat - $delta], [$lng + $delta, $lat + $delta],
                [$lng - $delta, $lat + $delta], [$lng - $delta, $lat - $delta],
            ]]],
        ];
    }

    private function dapodikRequest(string $path, array $query = []): array
    {
        if ($this->baseUrl !== 'https://dapo.kemendikdasmen.go.id') {
            throw new RuntimeException('Alamat direktori sekolah tidak diizinkan.');
        }

        $response = $this->http()
            ->withToken($this->token())
            ->get($this->baseUrl.$path, $query)
            ->throw()
            ->json();

        $data = $response['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Respons direktori sekolah tidak dapat dibaca.');
        }

        return $data;
    }

    private function fallbackRequest(string $path, array $query = []): array
    {
        if ($this->fallbackUrl !== 'https://sekolah.devapi.id') {
            throw new RuntimeException('Alamat cadangan direktori sekolah tidak diizinkan.');
        }

        $response = $this->http()->get($this->fallbackUrl.$path, $query)->throw()->json();
        $data = $response['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Respons cadangan direktori sekolah tidak dapat dibaca.');
        }

        return $data;
    }

    private function regionRequest(string $path): array
    {
        if ($this->regionUrl !== 'https://wilayah.id/api') {
            throw new RuntimeException('Alamat API wilayah tidak diizinkan.');
        }

        $response = $this->http()->get($this->regionUrl.$path)->throw()->json();
        $data = $response['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Respons API wilayah tidak dapat dibaca.');
        }

        return $data;
    }

    private function token(): string
    {
        if ($configured = config('services.dapodik.token')) {
            return (string) $configured;
        }

        return Cache::remember('dapodik.directory.public-token', now()->addHours(6), function () {
            $script = $this->http()->get($this->baseUrl.'/env.js')->throw()->body();
            if (! preg_match('/VITE_API_TOKEN:\s*"([^"]+)"/', $script, $matches)) {
                throw new RuntimeException('Token publik direktori sekolah tidak ditemukan.');
            }

            return $matches[1];
        });
    }

    private function regions(): array
    {
        return Cache::rememberForever('dapodik.directory.local-regions.v3', function () {
            $path = database_path('data/indonesia-school-regions.json');
            $regions = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
            if (! is_array($regions) || ! isset($regions['provinces'])) {
                throw new RuntimeException('Data wilayah lokal tidak ditemukan.');
            }

            return $regions;
        });
    }

    private function http()
    {
        return Http::acceptJson()
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; RuteAmanSchoolDirectory/1.0)'])
            ->withOptions(['verify' => (bool) config('services.dapodik.verify_ssl', true)])
            ->timeout((int) config('services.dapodik.timeout', 12))
            ->retry(2, 250);
    }

    private function normalizeDapodikSearch(array $item): array
    {
        return [
            'npsn' => strtoupper(trim((string) ($item['npsn'] ?? ''))),
            'name' => trim((string) ($item['nama'] ?? $item['nama_sekolah'] ?? $item['name'] ?? '')),
            'level' => (string) ($item['jenjang'] ?? ''),
            'status' => (string) ($item['status_sekolah'] ?? ''),
            'province' => (string) ($item['provinsi'] ?? ''),
            'regency' => (string) ($item['kabupaten'] ?? ''),
            'district' => (string) ($item['kecamatan'] ?? ''),
        ];
    }

    private function normalizeDapodikDetail(array $item): array
    {
        return $this->normalizeDapodikSearch($item) + [
            'address' => (string) ($item['alamat_jalan'] ?? ''),
            'latitude' => $item['lintang'] ?? null,
            'longitude' => $item['bujur'] ?? null,
        ];
    }

    private function normalizeFallback(array $item): array
    {
        $address = is_array($item['alamat'] ?? null) ? $item['alamat'] : [];
        $location = is_array($item['lokasi'] ?? null) ? $item['lokasi'] : [];

        return [
            'npsn' => strtoupper(trim((string) ($item['npsn'] ?? ''))),
            'name' => trim((string) ($item['nama'] ?? $item['nama_sekolah'] ?? $item['name'] ?? '')),
            'level' => (string) ($item['bentukPendidikan'] ?? $item['jenjangPendidikan'] ?? ''),
            'status' => (string) ($item['statusSatuanPendidikan'] ?? ''),
            'province' => (string) ($address['nama_provinsi'] ?? ''),
            'regency' => (string) ($address['nama_kabupaten'] ?? ''),
            'district' => (string) ($address['nama_kecamatan'] ?? ''),
            'address' => (string) ($address['jalan'] ?? ''),
            'latitude' => $location['lintang'] ?? null,
            'longitude' => $location['bujur'] ?? null,
        ];
    }

    private function sameRegion(string $actual, string $expected): bool
    {
        $normalize = function (string $value) {
            $value = strtolower($value);
            $value = preg_replace('/\bdaerah\s+khusus\s+ibukota\b/', 'dki', $value);
            $value = preg_replace('/\bdaerah\s+istimewa\b/', 'di', $value);
            $value = preg_replace('/^(provinsi|prov)\.?\s*/', '', $value);
            $value = preg_replace('/^kabupaten\s+|^kab\.?\s*/', 'kab ', $value);
            $value = preg_replace('/^kota\s+(administrasi|adm)\.?\s*/', 'kota ', $value);
            $value = preg_replace('/^(kecamatan|kec)\.?\s*/', '', $value);
            $value = preg_replace('/\b(administrasi|adm)\.?\b/', 'adm', $value);
            $value = preg_replace('/\b(kepulauan|kep)\.?\b/', 'kep', $value);

            return preg_replace('/[^a-z0-9]+/', '', $value);
        };

        return $normalize($actual) === $normalize($expected);
    }
}
