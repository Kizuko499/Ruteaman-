<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pk = collect(Schema::getIndexes('audit_logs'))->first(fn ($i) => $i['primary']);
        if (($pk['columns'] ?? []) === ['id']) {
            return;
        }
        if (! Schema::hasColumn('audit_logs', 'id')) {
            // No old identifier exists to invalidate. Both engines allocate unique IDs.
            if ($pk) {
                throw new RuntimeException('Audit memiliki PK lain tanpa id. Backup dan rekonsiliasi skema manual sebelum melanjutkan.');
            }
            Schema::table('audit_logs', fn (Blueprint $t) => $t->bigIncrements('id'));

            return;
        }
        if (DB::table('audit_logs')->whereNull('id')->exists() || DB::table('audit_logs')->select('id')->groupBy('id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Audit id NULL/duplikat: migrasi dihentikan tanpa mengubah entri. Rekonsiliasi salinan data dan referensi dulu; jangan menomori ulang histori produksi secara diam-diam.');
        }
        if (DB::getDriverName() === 'pgsql') {
            if ($pk) {
                $constraint = DB::selectOne("SELECT conname FROM pg_constraint WHERE conrelid='audit_logs'::regclass AND contype='p'")->conname;
                DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT "'.str_replace('"', '""', $constraint).'"');
            }
            DB::statement('ALTER TABLE audit_logs ALTER COLUMN id SET NOT NULL');
            DB::statement('ALTER TABLE audit_logs ADD PRIMARY KEY(id)');
            DB::statement('CREATE SEQUENCE IF NOT EXISTS audit_logs_id_seq');
            DB::statement("SELECT setval('audit_logs_id_seq',COALESCE((SELECT MAX(id) FROM audit_logs),0)+1,false)");
            DB::statement("ALTER TABLE audit_logs ALTER COLUMN id SET DEFAULT nextval('audit_logs_id_seq')");
            DB::statement('ALTER SEQUENCE audit_logs_id_seq OWNED BY audit_logs.id');
        } else {
            DB::statement('ALTER TABLE audit_logs '.($pk ? 'DROP PRIMARY KEY, ' : '').'MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(id)');
        }
    }

    public function down(): void
    { /* A verified historical identifier is intentionally retained on rollback. */
    }
};
