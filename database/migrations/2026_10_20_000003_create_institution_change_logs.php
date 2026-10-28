<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 50); // created, updated, verified, archived, restored
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_change_logs');
    }
};
