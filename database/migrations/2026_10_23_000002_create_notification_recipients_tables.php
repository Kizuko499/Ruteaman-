<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_recipients')) {
            Schema::create('notification_recipients', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('description')->nullable();
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamp('verified_at')->nullable();
                $table->char('verification_token_hash', 64)->nullable();
                $table->timestamp('verification_sent_at')->nullable();
                $table->string('mode', 16)->default('realtime');
                $table->string('frequency_type', 16)->default('weekly');
                $table->unsignedSmallInteger('interval_days')->nullable();
                $table->unsignedTinyInteger('weekly_day')->nullable();
                $table->string('monthly_mode', 8)->nullable();
                $table->unsignedTinyInteger('monthly_day')->nullable();
                $table->time('send_time')->default('08:00:00');
                $table->string('timezone', 64)->default('Asia/Jakarta');
                $table->time('quiet_start')->nullable();
                $table->time('quiet_end')->nullable();
                $table->boolean('send_empty_digest')->default(false);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamp('next_run_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['is_active', 'next_run_at']);
            });
        }

        if (! Schema::hasTable('notification_recipient_categories')) {
            Schema::create('notification_recipient_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recipient_id')->constrained('notification_recipients')->cascadeOnDelete();
                $table->string('category', 48);
                $table->unique(['recipient_id', 'category']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipient_categories');
        Schema::dropIfExists('notification_recipients');
    }
};
