<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_digest_runs')) {
            Schema::create('notification_digest_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recipient_id')->constrained('notification_recipients')->cascadeOnDelete();
                $table->string('period_key', 64);
                $table->date('period_start');
                $table->date('period_end');
                $table->string('status', 16)->default('queued');
                $table->foreignId('email_log_id')->nullable()->constrained('email_delivery_logs')->nullOnDelete();
                $table->timestamps();
                $table->unique(['recipient_id', 'period_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_digest_runs');
    }
};
