<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! config('ruteaman_audit.db_guards')) {
            return;
        }
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared("CREATE OR REPLACE FUNCTION ruteaman_audit_immutable() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Audit is append-only'; END; $$");
            DB::unprepared('DROP TRIGGER IF EXISTS ruteaman_audit_guard ON audit_logs');
            DB::unprepared('CREATE TRIGGER ruteaman_audit_guard BEFORE UPDATE OR DELETE ON audit_logs FOR EACH ROW EXECUTE FUNCTION ruteaman_audit_immutable()');
        } elseif (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            foreach (['UPDATE' => 'update', 'DELETE' => 'delete'] as $sql => $name) {
                DB::unprepared("DROP TRIGGER IF EXISTS ruteaman_audit_{$name}");
                DB::unprepared("CREATE TRIGGER ruteaman_audit_{$name} BEFORE {$sql} ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Audit is append-only'");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS ruteaman_audit_guard ON audit_logs');
            DB::unprepared('DROP FUNCTION IF EXISTS ruteaman_audit_immutable()');
        } elseif (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            foreach (['update', 'delete'] as $n) {
                DB::unprepared("DROP TRIGGER IF EXISTS ruteaman_audit_{$n}");
            }
        }
    }
};
