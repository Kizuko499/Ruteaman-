<?php

namespace App\Console\Commands;

use App\Services\SpatialSql;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class RestoreSchoolCatchments extends Command
{
    protected $signature = 'schools:restore-catchments {file : Nama berkas snapshot di storage/app/backups}';

    protected $description = 'Pulihkan catchment + features institusi dari snapshot yang dibuat schools:backfill-catchments.';

    public function handle(): int
    {
        $path = storage_path('app/backups/'.basename((string) $this->argument('file')));
        if (! is_file($path)) {
            $this->error("Snapshot tidak ditemukan: {$path}");

            return self::FAILURE;
        }
        $snapshot = json_decode((string) file_get_contents($path), true);
        if (! is_array($snapshot) || ! isset($snapshot['rows']) || ! is_array($snapshot['rows'])) {
            $this->error('Format snapshot tidak valid.');

            return self::FAILURE;
        }
        $restored = 0;
        foreach ($snapshot['rows'] as $row) {
            DB::table('schools')->where('id', (int) $row['id'])->update([
                'catchment' => ($row['catchment_wkt'] ?? null) ? SpatialSql::expression((string) $row['catchment_wkt']) : null,
                'features' => json_encode($row['features'] ?? []),
            ]);
            $restored++;
        }
        $this->info("Dipulihkan {$restored} institusi dari snapshot ".basename($path).' (dibuat '.($snapshot['created_at'] ?? '?').').');

        return self::SUCCESS;
    }
}
