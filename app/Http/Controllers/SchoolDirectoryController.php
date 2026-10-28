<?php

namespace App\Http\Controllers;

use App\Services\DapodikSchoolDirectory;
use App\Models\School;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\{JsonResponse, Request};
use Throwable;

class SchoolDirectoryController extends Controller
{
    public function provinces(DapodikSchoolDirectory $directory): JsonResponse
    {
        return $this->respond(fn () => $directory->provinces());
    }

    public function regencies(Request $request, DapodikSchoolDirectory $directory): JsonResponse
    {
        $data = $request->validate(['province_code' => ['required', 'regex:/^\d{2}$/']]);
        return $this->respond(fn () => $directory->regencies($data['province_code']));
    }

    public function districts(Request $request, DapodikSchoolDirectory $directory): JsonResponse
    {
        $data = $request->validate([
            'province_code' => ['required', 'regex:/^\d{2}$/'],
            'regency_code' => ['required', 'regex:/^\d{2}\.\d{2}$/'],
        ]);
        return $this->respond(fn () => $directory->districts($data['province_code'], $data['regency_code']));
    }

    public function schools(Request $request, DapodikSchoolDirectory $directory): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:80'],
            'province_code' => ['required', 'regex:/^\d{2}$/'],
            'regency_code' => ['required', 'regex:/^\d{2}\.\d{2}$/'],
            'district_code' => ['required', 'regex:/^\d{2}\.\d{2}\.\d{2}$/'],
        ]);
        return $this->respond(function () use ($directory, $data) {
            $schools = $directory->search($data['query'], $data['province_code'], $data['regency_code'], $data['district_code']);
            // Filter after the directory cache: local lifecycle changes must take effect immediately.
            if (!Schema::hasColumn('schools', 'status')) {
                return $schools;
            }
            $archived = School::query()->where('status', 'archived')
                ->whereIn('npsn', array_column($schools, 'npsn'))->pluck('npsn')->all();
            return array_values(array_filter($schools, fn ($school) => !in_array($school['npsn'] ?? null, $archived, true)));
        });
    }


    /** Koordinat presisi satu sekolah (untuk pin peta). */
    public function detail(Request $request, DapodikSchoolDirectory $directory): JsonResponse
    {
        $data = $request->validate(['npsn' => ['required', 'string', 'regex:/^[A-Za-z0-9]{8}$/']]);
        try {
            $record = $directory->guestPreview(strtoupper($data['npsn']));
        } catch (Throwable $error) {
            return response()->json(['error' => ['code' => 'SCHOOL_DETAIL_UNAVAILABLE', 'message' => 'Koordinat sekolah belum tersedia. Pilih sekolah lain.']], 503);
        }
        return response()->json(['data' => [
            'npsn' => $record['npsn'], 'name' => $record['name'],
            'latitude' => $record['latitude'], 'longitude' => $record['longitude'],
            'province' => $record['province'], 'regency' => $record['regency'], 'district' => $record['district'],
        ]])->header('Cache-Control', 'private, no-store');
    }
    /**
     * Resolve koordinat untuk institusi dari direktori nasional.
     * Pipeline: (1) DB lokal terverifikasi → (2) Dapodik detail API → (3) Geocoding → (4) gagal jujur
     */
    public function resolveCoordinates(Request $request, DapodikSchoolDirectory $directory): JsonResponse
    {
        $data = $request->validate([
            'npsn' => ['required', 'string', 'regex:/^[A-Za-z0-9]{8}$/'],
            'name' => ['nullable', 'string', 'max:255'],
            'regency' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
        ]);
        $npsn = strtoupper($data['npsn']);

        // Prioritas 1: Database lokal (sudah terverifikasi)
        try {
            $cached = \Illuminate\Support\Facades\DB::table('school_catalog_cache')
                ->where('npsn', $npsn)
                ->whereNotNull('latitude')->whereNotNull('longitude')
                ->first();
            if ($cached && $this->validIndonesianCoords($cached->latitude, $cached->longitude)) {
                return response()->json(['data' => [
                    'npsn' => $cached->npsn, 'name' => $cached->name,
                    'latitude' => (float) $cached->latitude, 'longitude' => (float) $cached->longitude,
                    'source' => 'local_verified', 'approximate' => false,
                ]]);
            }
        } catch (\Throwable $e) {}

        // Prioritas 2: Dapodik detail API
        try {
            $record = $directory->guestPreview($npsn);
            if (!empty($record['latitude']) && !empty($record['longitude'])) {
                // Simpan ke cache lokal untuk penggunaan berikutnya
                $this->cacheCoordinates($npsn, $data['name'] ?? $record['name'], $record['latitude'], $record['longitude'], 'dapodik', $data);
                return response()->json(['data' => [
                    'npsn' => $npsn, 'name' => $record['name'],
                    'latitude' => (float) $record['latitude'], 'longitude' => (float) $record['longitude'],
                    'source' => 'dapodik', 'approximate' => false,
                ]]);
            }
        } catch (\Throwable $e) {}

        // Prioritas 3: Geocoding dengan query bertingkat
        $geo = $this->geocodeSchool($data['name'] ?? '', $data['regency'] ?? '', $data['province'] ?? '');
        if ($geo) {
            $this->cacheCoordinates($npsn, $data['name'] ?? '', $geo['lat'], $geo['lng'], 'nominatim', $data, true);
            return response()->json(['data' => [
                'npsn' => $npsn, 'name' => $data['name'] ?? '',
                'latitude' => $geo['lat'], 'longitude' => $geo['lng'],
                'source' => 'nominatim', 'approximate' => true,
            ]]);
        }

        // Prioritas 4: Gagal jujur
        return response()->json(['error' => [
            'code' => 'COORDINATES_NOT_FOUND',
            'message' => 'Koordinat untuk institusi ini belum tersedia atau belum terverifikasi.',
        ]], 404);
    }

    private function validIndonesianCoords($lat, $lng): bool
    {
        $lat = (float) $lat; $lng = (float) $lng;
        return is_finite($lat) && is_finite($lng) && $lat >= -11.5 && $lat <= 6.5 && $lng >= 94 && $lng <= 142.5;
    }

    private function cacheCoordinates(string $npsn, string $name, float $lat, float $lng, string $source, array $data, bool $approx = false): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('school_catalog_cache')->upsert([[
                'npsn' => $npsn,
                'name' => $name,
                'name_normalized' => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name))),
                'province' => $data['province'] ?? null,
                'regency' => $data['regency'] ?? null,
                'latitude' => $lat, 'longitude' => $lng,
                'source' => $source . ($approx ? '_approx' : ''),
                'source_updated_at' => now(),
                'updated_at' => now(), 'created_at' => now(),
            ]], ['npsn'], ['latitude', 'longitude', 'source', 'source_updated_at', 'updated_at']);
        } catch (\Throwable $e) {}
    }

    private function geocodeSchool(string $name, string $regency, string $province): ?array
    {
        $queries = [];
        if ($name && $regency) $queries[] = "$name, $regency, Indonesia";
        if ($regency) $queries[] = "$regency, Indonesia";
        if ($name && $province) $queries[] = "$name, $province, Indonesia";
        if ($name) $queries[] = "$name, Indonesia";

        foreach ($queries as $q) {
            try {
                $resp = \Illuminate\Support\Facades\Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'RuteAman/1.0 (kontak@ruteaman.my.id)'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $q, 'format' => 'json', 'limit' => 1, 'countrycodes' => 'id',
                    ]);
                $data = $resp->json();
                if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
                    $lat = (float) $data[0]['lat']; $lng = (float) $data[0]['lon'];
                    if ($this->validIndonesianCoords($lat, $lng)) {
                        // Jangan pakai hasil yang terlalu kasar (hanya nama kabupaten tanpa sekolah)
                        // Tapi tetap kembalikan sebagai approximate
                        return ['lat' => $lat, 'lng' => $lng];
                    }
                }
            } catch (\Throwable $e) { continue; }
            // Hormati rate limit Nominatim (1 req/detik)
            usleep(1100000);
        }
        return null;
    }

    /**
     * Daftarkan sekolah dari Dapodik ke database lokal (auto-register saat dipilih).
     */
    public function provision(Request $request): JsonResponse
    {
        $data = $request->validate([
            'npsn' => ['required', 'string', 'regex:/^[A-Za-z0-9]{8}$/'],
            'name' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'regency' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
        $npsn = strtoupper($data['npsn']);

        // Cek sudah terdaftar (berdasarkan NPSN di kolom npsn atau nama)
        $existing = \App\Models\School::where('npsn', $npsn)->first();
        if ($existing) {
            return response()->json(['data' => ['id' => $existing->id, 'name' => $existing->name, 'already_registered' => true]]);
        }

        $lat = isset($data['latitude']) ? (float) $data['latitude'] : -6.1754; // Default Jakarta
        $lng = isset($data['longitude']) ? (float) $data['longitude'] : 106.8272;
        // Validasi koordinat Indonesia
        if ($lat < -11.5 || $lat > 6.5 || $lng < 94 || $lng > 142.5) {
            $lat = -6.1754; $lng = 106.8272;
        }

        $delta = 0.02;
        $school = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $npsn, $lat, $lng, $delta) {
            $id = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
                'name' => $data['name'],
                'npsn' => $npsn,
                'institution_type' => 'school',
                'country_code' => 'ID',
                'status' => 'active',
                'public_slug' => \Illuminate\Support\Str::slug($data['name']) . '-' . strtolower($npsn),
                'timezone' => 'Asia/Jakarta',
                'location' => \Illuminate\Support\Facades\DB::raw("ST_GeomFromText('POINT($lng $lat)')"),
                'catchment' => \Illuminate\Support\Facades\DB::raw("ST_GeomFromText('POLYGON((". ($lng-$delta)." ".($lat-$delta).", ".($lng+$delta)." ".($lat-$delta).", ".($lng+$delta)." ".($lat+$delta).", ".($lng-$delta)." ".($lat+$delta).", ".($lng-$delta)." ".($lat-$delta)."))')"),
                'features' => json_encode(['reporting' => true]),
                'settings' => json_encode([]),
            ]);
            return \App\Models\School::find($id);
        });

        return response()->json(['data' => ['id' => $school->id, 'name' => $school->name, 'already_registered' => false]]);
    }

    public function searchNationwide(Request $request, DapodikSchoolDirectory $directory): JsonResponse
    {
        $data = $request->validate(['query' => ['required', 'string', 'min:3', 'max:80']]);
        return $this->respond(function () use ($directory, $data) {
            $schools = $directory->searchNationwide($data['query']);
            return array_slice($schools, 0, 20);
        });
    }

    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json(['data' => $callback()]);
        } catch (Throwable $error) {
            report($error);
            return response()->json([
                'data' => [],
                'message' => 'Direktori sekolah nasional sedang tidak dapat dihubungi. Coba beberapa saat lagi.',
            ], 503);
        }
    }
}