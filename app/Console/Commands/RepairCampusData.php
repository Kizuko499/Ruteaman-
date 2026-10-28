<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairCampusData extends Command
{
    protected $signature = 'institutions:repair-campus {--dry-run : Tampilkan rencana tanpa mengubah data}';

    protected $description = 'Balik tipe kampus ROR yang terimpor sebagai sekolah dan gabung baris dobel (nama + koordinat sama).';

    /**
     * Tentukan baris yang dipertahankan dan yang digabung dalam satu grup dobel.
     * Prioritas: baris bertipe campus dipertahankan; sisanya ID terkecil.
     */
    public function planMerges(array $rows): array
    {
        usort($rows, function ($a, $b) {
            $ca = ($a['institution_type'] ?? '') === 'campus' ? 0 : 1;
            $cb = ($b['institution_type'] ?? '') === 'campus' ? 0 : 1;
            return $ca <=> $cb ?: ((int) $a['id'] <=> (int) $b['id']);
        });
        $keep = array_shift($rows);

        return ['keep' => $keep, 'merge' => array_values($rows)];
    }

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $csvPath = database_path('seeders/data/kampus_ror_indonesia.csv');
        if (! is_file($csvPath)) {
            $this->error('Berkas daftar kampus tidak ditemukan: ' . $csvPath);
            return self::FAILURE;
        }

        // 1) Balik tipe berdasarkan daftar nama kampus ROR
        $names = [];
        if (($h = fopen($csvPath, 'r')) !== false) {
            fgetcsv($h);
            while (($r = fgetcsv($h)) !== false) {
                if (! empty($r[0])) {
                    $names[] = trim($r[0]);
                }
            }
            fclose($h);
        }
        $names = array_values(array_unique($names));
        $this->info('Daftar kampus acuan: ' . count($names) . ' nama.');

        $flipped = 0;
        foreach (array_chunk($names, 500) as $chunk) {
            $q = DB::table('schools')->whereIn('name', $chunk)->where('institution_type', '!=', 'campus');
            $flipped += $dry ? $q->count() : $q->update(['institution_type' => 'campus']);
        }
        $this->info("Tipe dibalik ke campus: $flipped baris" . ($dry ? ' (dry-run)' : ''));

        // 2) Gabung dobel: grup nama + koordinat sama (5 desimal)
        $all = DB::select('SELECT id, name, institution_type, ST_X(location) AS lng, ST_Y(location) AS lat FROM schools');
        $groups = [];
        foreach ($all as $r) {
            $k = mb_strtolower(trim($r->name)) . '|' . round((float) $r->lat, 5) . '|' . round((float) $r->lng, 5);
            $groups[$k][] = ['id' => (int) $r->id, 'institution_type' => $r->institution_type];
        }

        $refs = [
            ['users', 'school_id'],
            ['saved_routes', 'school_id'],
            ['home_gallery_groups', 'school_id'],
            ['campus_catalog', 'school_id'],
            ['hazard_reports', 'school_id'],
            ['institution_suggestions', 'institution_id'],
        ];

        $merged = 0;
        $skipped = 0;
        foreach ($groups as $rows) {
            if (count($rows) < 2) {
                continue;
            }
            ['keep' => $keep, 'merge' => $dups] = $this->planMerges($rows);
            foreach ($dups as $dup) {
                if ($dry) {
                    $merged++;
                    continue;
                }
                try {
                    DB::transaction(function () use ($refs, $keep, $dup) {
                        foreach ($refs as [$table, $col]) {
                            if (Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
                                DB::table($table)->where($col, $dup['id'])->update([$col => $keep['id']]);
                            }
                        }
                        if (Schema::hasTable('institution_change_logs')) {
                            DB::table('institution_change_logs')->where('school_id', $dup['id'])->delete();
                        }
                        DB::table('schools')->where('id', $dup['id'])->delete();
                    });
                    $merged++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $this->warn('Dobel ID ' . $dup['id'] . ' dilewati: ' . $e->getMessage());
                }
            }
        }
        $this->info("Baris dobel digabung: $merged" . ($dry ? ' (dry-run)' : '') . ", dilewati karena bentrok: $skipped");

        return self::SUCCESS;
    }
}
