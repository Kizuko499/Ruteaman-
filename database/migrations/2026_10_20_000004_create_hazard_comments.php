<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazard_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hazard_id')->constrained('hazards')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['hazard_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hazard_comments');
    }
};
