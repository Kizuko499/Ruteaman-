<?php

namespace App\Console\Commands;

use App\Services\GeoGeometry;
use App\Services\SpatialSql;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class BackfillSchoolCatchments extends Command
{
    protected $signature = 'schools:backfill-catchments {--radius=2000 : Radius area bawaan dalam meter (100-2800)} {--force : Buat ulang juga area yang sebelumnya dibuat otomatis perintah ini} {--dry-run : Tampilkan hitungan tanpa menulis apa pun}';

    protected $description = 'Isi area wilayah (catchment) lingkaran bawaan untuk institusi yang belum memilikinya; area gambar tangan tidak pernah disentuh.';

    public function handle(): int
    {
        $radius = max(100, min(2800, (int) $this->option('radius')));
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $base = DB::table('schools')->where('status', '!=', 'archived');
        $withoutLocation = (clone $base)->whereNull('location')->count();
        $alreadyManual = (clone $base)->whereNotNull('catchment')
            ->get(['features'])
            ->reject(fn ($r) => (bool) (json_decode($r->features ?? '[]', true)['catchment_auto'] ?? false))
            ->count();

        $rows = (clone $base)->whereNotNull('location')
            ->select('id', 'features')
            ->selectRaw(SpatialSql::coordinates())
            ->selectRaw('catchment IS NOT NULL AS has_catchment')
            ->selectRaw('ST_AsText(catchment) AS catchment_wkt')
            ->orderBy('id')
            ->get();

        $changes = [];
        $skippedAuto = 0;
        foreach ($rows as $row) {
            $features = json_decode($row->features ?? '[]', true) ?: [];
            $isAuto = (bool) ($features['catchment_auto'] ?? false);
            if ((bool) $row->has_catchment && ! ($force && $isAuto)) {
                if ($isAuto) {
                    $skippedAuto++;
                }
                continue;
            }
            $changes[] = $row;
        }

        if (! $dry && $changes !== []) {
            $snapshot = [
                'created_at' => now()->toIso8601String(),
                'radius' => $radius,
                'force' => $force,
                'rows' => array_map(fn ($row) => [
                    'id' => (int) $row->id,
                    'catchment_wkt' => $row->catchment_wkt,
                    'features' => json_decode($row->features ?? '[]', true) ?: [],
                ], $changes),
            ];
            $dir = storage_path('app/backups');
            \Illuminate\Support\Facades\File::ensureDirectoryExists($dir);
            $name = 'catchment-backfill-'.now()->format('Ymd-His').'.json';
            file_put_contents($dir.'/'.$name, json_encode($snapshot));
            $this->info("Snapshot pengaman tersimpan: storage/app/backups/{$name} (pulihkan dengan schools:restore-catchments {$name}).");
        }

        $filled = 0;
        foreach ($changes as $row) {
            $filled++;
            if ($dry) {
                continue;
            }
            $features = json_decode($row->features ?? '[]', true) ?: [];
            $features['catchment_auto'] = true;
            DB::table('schools')->where('id', $row->id)->update([
                'catchment' => SpatialSql::expression(GeoGeometry::circleWkt((float) $row->longitude, (float) $row->latitude, $radius)),
                'features' => json_encode($features),
            ]);
        }

        $this->info(($dry ? '[dry-run] ' : '')."Radius {$radius} m — area diisi/dibuat ulang: {$filled}; area otomatis lama dilewati (tanpa --force): {$skippedAuto}; area gambar tangan (tidak disentuh): {$alreadyManual}; tanpa koordinat (dilewati): {$withoutLocation}.");

        return self::SUCCESS;
    }
}
