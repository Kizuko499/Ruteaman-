<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_delivery_logs')) {
            Schema::create('email_delivery_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->char('recipient_hash', 64)->index();
                $table->string('recipient_masked');
                $table->string('category', 48)->index();
                $table->string('subject');
                $table->string('status', 16)->default('queued')->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->text('error')->nullable();
                $table->json('payload')->nullable();
                $table->string('dedupe_key')->nullable()->unique();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_delivery_logs');
    }
};
