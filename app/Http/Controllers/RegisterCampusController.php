<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\CampusDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterCampusController extends Controller
{
    /**
     * Cari kampus untuk form registrasi: gabungan DB lokal + ROR.
     * GET /api/register-campus-search?query=...
     */
    public function search(Request $request, CampusDirectory $directory)
    {
        $query = trim($request->input('query', ''));
        if (mb_strlen($query) < 3) {
            return response()->json(['data' => [], 'message' => 'Ketik minimal 3 karakter.']);
        }

        $results = [];
        $number = 1;

        // 1. Cari di DB lokal (kampus aktif)
        $local = School::where('institution_type', 'campus')
            ->where('status', 'active')
            ->where('name', 'like', '%' . $query . '%')
            ->limit(10)
            ->get(['id', 'name', 'country_code', 'campus_code']);

        foreach ($local as $school) {
            $results[] = [
                'number' => $number++,
                'source' => 'registered',
                'id' => $school->id,
                'name' => $school->name,
                'country_code' => $school->country_code,
                'campus_code' => $school->campus_code,
                'display' => $school->name . ' · ' . ($school->country_code ?? 'ID'),
            ];
        }

        // 2. Cari di direktori lokal yang dibundel (katalog pemerintah/PDDikti + ROR
        //    di resources/data) — tanpa panggilan jaringan, skip yang sudah ada di DB.
        try {
            $catalogResults = $directory->searchLocal($query, 14);
            $localNames = $local->pluck('name')->map(fn($n) => mb_strtolower($n))->toArray();

            foreach ($catalogResults as $item) {
                if (in_array(mb_strtolower($item['name'] ?? ''), $localNames)) {
                    continue;
                }
                $results[] = [
                    'number' => $number++,
                    'source' => 'ror',
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'city' => $item['city'] ?? null,
                    'country_code' => $item['country_code'] ?? 'ID',
                    'campus_code' => $item['campus_code'] ?? null,
                    'latitude' => $item['latitude'] ?? null,
                    'longitude' => $item['longitude'] ?? null,
                ];
                if ($number > 20) break;
            }
        } catch (\Throwable $e) {
            // Direktori lokal gagal, tetap kembalikan hasil lokal
        }

        return response()->json(['data' => $results]);
    }

    /**
     * Tambahkan kampus dari ROR ke DB lokal saat dipilih.
     * POST /api/register-campus-provision
     */
    public function provision(Request $request)
    {
        $data = $request->validate([
            'ror_id' => ['required', 'string', function ($attribute, $value, $fail) {
                if (preg_match('/^https:\/\/ror\.org\/[a-z0-9]{9}$/', $value)) {
                    return;
                }
                if (preg_match('/^pddikti:\d{4,8}$/', $value)) {
                    return;
                }
                $fail('ID kampus tidak valid.');
            }],
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        // Kode kampus dari ID direktori (ROR: / PDDIKTI:)
        $campusCode = str_starts_with($data['ror_id'], 'pddikti:')
            ? 'PDDIKTI:' . substr($data['ror_id'], strlen('pddikti:'))
            : 'ROR:' . basename($data['ror_id']);

        // Cek apakah sudah ada (berdasarkan nama atau kode kampus)
        $existing = School::where('institution_type', 'campus')
            ->where(function ($q) use ($data, $campusCode) {
                $q->where('name', $data['name'])->orWhere('campus_code', $campusCode);
            })
            ->first();

        if ($existing) {
            return response()->json([
                'id' => $existing->id,
                'created' => false,
                'message' => 'Kampus sudah terdaftar.',
            ]);
        }

        $lat = (float)($data['latitude'] ?? -6.2);
        $lng = (float)($data['longitude'] ?? 106.8);
        $countryCode = strtoupper($data['country_code'] ?? 'ID');

        // Buat catchment polygon sederhana (±5km dari titik)
        $delta = 0.045;
        $lngMin = $lng - $delta; $lngMax = $lng + $delta;
        $latMin = $lat - $delta; $latMax = $lat + $delta;
        $polygon = "POLYGON(($lngMin $latMin,$lngMax $latMin,$lngMax $latMax,$lngMin $latMax,$lngMin $latMin))";

        $schoolId = DB::transaction(function () use ($data, $lat, $lng, $countryCode, $polygon, $campusCode) {
            $slug = Str::slug($data['name']) . '-' . Str::random(6);
            $features = json_encode(['reporting' => true]);
            $settings = json_encode([]);

            // Insert langsung dengan kolom spatial (location & catchment NOT NULL)
            $isPgsql = DB::getDriverName() === 'pgsql';
            if ($isPgsql) {
                $id = DB::table('schools')->insertGetId([
                    'name' => $data['name'],
                    'institution_type' => 'campus',
                    'country_code' => $countryCode,
                    'status' => 'active',
                    'public_slug' => $slug,
                    'timezone' => 'Asia/Jakarta',
                    'campus_code' => $campusCode,
                    'features' => $features,
                    'settings' => $settings,
                    'location' => DB::raw("ST_SetSRID(ST_MakePoint($lng, $lat), 4326)"),
                    'catchment' => DB::raw("ST_GeomFromText('$polygon', 4326)"),
                ]);
            } else {
                $id = DB::table('schools')->insertGetId([
                    'name' => $data['name'],
                    'institution_type' => 'campus',
                    'country_code' => $countryCode,
                    'status' => 'active',
                    'public_slug' => $slug,
                    'timezone' => 'Asia/Jakarta',
                    'campus_code' => $campusCode,
                    'features' => $features,
                    'settings' => $settings,
                    'location' => DB::raw("ST_PointFromText('POINT($lng $lat)', 4326)"),
                    'catchment' => DB::raw("ST_PolygonFromText('$polygon')"),
                ]);
            }

            return $id;
        });

        $school = School::find($schoolId);

        return response()->json([
            'id' => $schoolId,
            'created' => true,
            'message' => 'Kampus ditambahkan.',
        ]);
    }
}
