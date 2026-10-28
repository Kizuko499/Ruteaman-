<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->string('category', 50); // new_school, wrong_name, wrong_address, wrong_location, closed, duplicate, other
            $table->foreignId('institution_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('proposed_name', 255);
            $table->string('npsn', 20)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('regency', 100)->nullable();
            $table->string('address', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('status', 20)->default('new'); // new, reviewing, waiting_info, approved, rejected, done
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_suggestions');
    }
};
