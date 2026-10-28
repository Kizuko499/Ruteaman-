<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = ['occurred_at', 'actor_name', 'actor_email', 'actor_role', 'school_id', 'category', 'severity', 'result', 'target_type', 'target_id', 'target_label', 'changes', 'meta', 'reason', 'ip_address', 'user_agent', 'request_id', 'route_name', 'http_method'];

    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $t) {
            if (! Schema::hasColumn('audit_logs', 'occurred_at')) {
                $t->timestamp('occurred_at', 6)->nullable();
            }
            foreach (['actor_name' => 100, 'actor_email' => 254, 'actor_role' => 32, 'category' => 32, 'severity' => 16, 'result' => 16, 'target_type' => 64, 'target_label' => 255, 'ip_address' => 45, 'user_agent' => 512, 'request_id' => 36, 'route_name' => 150, 'http_method' => 10] as $c => $length) {
                if (! Schema::hasColumn('audit_logs', $c)) {
                    $t->string($c, $length)->nullable();
                }
            }
            foreach (['school_id', 'target_id'] as $c) {
                if (! Schema::hasColumn('audit_logs', $c)) {
                    $t->unsignedBigInteger($c)->nullable();
                }
            }
            foreach (['changes', 'meta'] as $c) {
                if (! Schema::hasColumn('audit_logs', $c)) {
                    $t->json($c)->nullable();
                }
            }
            if (! Schema::hasColumn('audit_logs', 'reason')) {
                $t->text('reason')->nullable();
            }
        });
        $names = array_column(Schema::getIndexes('audit_logs'), 'name');
        foreach (['audit_created' => ['created_at'], 'audit_actor_created' => ['actor_id', 'created_at'], 'audit_action_created' => ['action', 'created_at'], 'audit_school_created' => ['school_id', 'created_at'], 'audit_occurred_id' => ['occurred_at', 'id']] as $name => $cols) {
            if (! in_array($name, $names, true)) {
                Schema::table('audit_logs', fn (Blueprint $t) => $t->index($cols, $name));
            }
        }
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $t) {
            foreach (['audit_created', 'audit_actor_created', 'audit_action_created', 'audit_school_created', 'audit_occurred_id'] as $i) {
                $t->dropIndex($i);
            }$t->dropColumn($this->columns);
        });
    }
};
