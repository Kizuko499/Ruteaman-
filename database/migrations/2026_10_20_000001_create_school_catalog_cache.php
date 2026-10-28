<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_catalog_cache', function (Blueprint $table) {
            $table->id();
            // NPSN sebagai string (jangan integer — digit nol di depan harus utuh)
            $table->string('npsn', 20)->unique();
            $table->string('name', 255);
            // Kolom pencarian yang dinormalisasi (lowercase, spasi tunggal)
            $table->string('name_normalized', 255)->index();
            $table->string('level', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('province', 100)->nullable()->index();
            $table->string('regency', 100)->nullable()->index();
            $table->string('district', 100)->nullable();
            $table->string('address', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source', 50)->default('dapodik');
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();

            // Index untuk pencarian cepat
            $table->index(['province', 'regency']);
            $table->fullText(['name', 'name_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_catalog_cache');
    }
};
