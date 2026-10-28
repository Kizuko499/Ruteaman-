<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminImportController extends Controller
{
    public function show()
    {
        return view('admin.import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('csv')->getRealPath();
        $rows = [];
        $errors = [];

        if (($h = fopen($path, 'r')) !== false) {
            $header = fgetcsv($h);
            $expected = ['name', 'npsn', 'latitude', 'longitude'];
            $headerLower = array_map(fn($x) => strtolower(trim($x)), $header ?? []);

            foreach ($expected as $col) {
                if (!in_array($col, $headerLower, true)) {
                    $errors[] = "Kolom '$col' tidak ditemukan. Format: " . implode(',', $expected);
                    break;
                }
            }

            if (empty($errors)) {
                $idx = array_flip($headerLower);
                $seenNames = [];
                $seenNpsn = [];
                $line = 1;
                while (($row = fgetcsv($h)) !== false) {
                    $line++;
                    if (count($row) < 4) continue;

                    $name = trim($row[$idx['name']] ?? '');
                    $npsn = trim($row[$idx['npsn']] ?? '');
                    $lat = trim($row[$idx['latitude']] ?? '');
                    $lng = trim($row[$idx['longitude']] ?? '');
                    $type = isset($idx['institution_type']) ? strtolower(trim($row[$idx['institution_type']] ?? '')) : 'school';
                    if ($type === '') $type = 'school';

                    $rowErrors = [];
                    if (!in_array($type, ['school', 'campus'], true)) {
                        $rowErrors[] = "Tipe tidak dikenal: $type";
                        $type = 'school';
                    }
                    if (empty($name)) $rowErrors[] = 'Nama kosong';
                    if (!is_numeric($lat) || $lat < -90 || $lat > 90) $rowErrors[] = "Latitude tidak valid: $lat";
                    if (!is_numeric($lng) || $lng < -180 || $lng > 180) $rowErrors[] = "Longitude tidak valid: $lng";

                    // Cek duplikat: di database, lalu di dalam berkas ini sendiri
                    $dup = null;
                    if (!empty($npsn)) {
                        $dup = School::where('npsn', strtoupper($npsn))->first();
                    }
                    if (!$dup && !empty($name)) {
                        $dup = School::where('name', $name)->first();
                    }
                    $dupNote = $dup ? $dup->name . ' (ID:' . $dup->id . ')' : null;
                    $nameKey = mb_strtolower($name);
                    if (!$dupNote && $name !== '' && isset($seenNames[$nameKey])) {
                        $dupNote = 'Duplikat di dalam berkas ini (baris ' . $seenNames[$nameKey] . ')';
                    }
                    if (!$dupNote && !empty($npsn) && isset($seenNpsn[strtoupper($npsn)])) {
                        $dupNote = 'NPSN duplikat di dalam berkas ini (baris ' . $seenNpsn[strtoupper($npsn)] . ')';
                    }
                    if ($name !== '') $seenNames[$nameKey] = $line;
                    if (!empty($npsn)) $seenNpsn[strtoupper($npsn)] = $line;

                    $rows[] = [
                        'line' => $line,
                        'name' => $name,
                        'npsn' => strtoupper($npsn),
                        'latitude' => (float) $lat,
                        'longitude' => (float) $lng,
                        'institution_type' => $type,
                        'errors' => $rowErrors,
                        'duplicate' => $dupNote,
                    ];

                    if (count($rows) >= 3000) {
                        $errors[] = 'Maksimal 3000 baris per impor.';
                        break;
                    }
                }
            }
            fclose($h);
        }

        // Simpan ke session untuk proses import
        session(['import_preview' => $rows]);

        return view('admin.import', ['rows' => $rows, 'previewErrors' => $errors]);
    }

    /**
     * Download semua sekolah sebagai CSV.
     */
    public function export()
    {
        $schools = School::orderBy('name')->get(['id', 'name', 'npsn', 'institution_type']);

        $callback = function () use ($schools) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['name', 'npsn', 'latitude', 'longitude', 'institution_type']);
            foreach ($schools as $s) {
                // Ambil lat/lng dari kolom spatial
                $coords = \Illuminate\Support\Facades\DB::selectOne(
                    "SELECT ST_X(location) as lng, ST_Y(location) as lat FROM schools WHERE id = ?",
                    [$s->id]
                );
                fputcsv($h, [
                    $s->name,
                    $s->npsn,
                    $coords->lat ?? '',
                    $coords->lng ?? '',
                    $s->institution_type,
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sekolah-' . date('Ymd') . '.csv"',
        ]);
    }

    public function process(Request $request)
    {
        $rows = session('import_preview', []);
        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data pratinjau. Unggah ulang berkas CSV-nya.');
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, &$imported, &$skipped) {
            foreach ($rows as $row) {
                if (!empty($row['errors']) || !empty($row['duplicate'])) {
                    $skipped++;
                    continue;
                }

                // Cek ulang duplikat saat proses (pratinjau bisa kedaluwarsa)
                $exists = null;
                if (!empty($row['npsn'])) {
                    $exists = School::where('npsn', strtoupper($row['npsn']))->first();
                }
                if (!$exists && !empty($row['name'])) {
                    $exists = School::where('name', $row['name'])->first();
                }
                if ($exists) {
                    $skipped++;
                    continue;
                }

                $lat = $row['latitude'];
                $lng = $row['longitude'];
                $delta = 0.02;

                $newId = DB::table('schools')->insertGetId([
                    'name' => $row['name'],
                    'npsn' => $row['npsn'] ?: null,
                    'institution_type' => ($row['institution_type'] ?? 'school') === 'campus' ? 'campus' : 'school',
                    'country_code' => 'ID',
                    'status' => 'active',
                    'public_slug' => Str::slug($row['name']) . '-' . strtolower(Str::random(6)),
                    'timezone' => 'Asia/Jakarta',
                    'location' => DB::raw("ST_GeomFromText('POINT($lng $lat)')"),
                    'catchment' => DB::raw("ST_GeomFromText('POLYGON((" . ($lng-$delta)." ".($lat-$delta).", ".($lng+$delta)." ".($lat-$delta).", ".($lng+$delta)." ".($lat+$delta).", ".($lng-$delta)." ".($lat+$delta).", ".($lng-$delta)." ".($lat-$delta)."))')"),
                    'features' => json_encode(['reporting' => true]),
                    'settings' => json_encode([]),
                ]);
                \App\Models\InstitutionChangeLog::log($newId, 'created', null,
                    ['name' => $row['name'], 'npsn' => $row['npsn'], 'institution_type' => ($row['institution_type'] ?? 'school') === 'campus' ? 'campus' : 'school'],
                    auth()->id(), 'Import CSV');
                $imported++;
            }
        });

        session()->forget('import_preview');

        return redirect()->route('admin.schools')->with('success', "Impor selesai: $imported ditambahkan, $skipped dilewati.");
    }
}
