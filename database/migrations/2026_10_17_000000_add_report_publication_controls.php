<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazards', fn (Blueprint $t) => $t->string('publication_control', 16)->default('normal')->index());
    }

    public function down(): void
    {
        Schema::table('hazards', function (Blueprint $t) {
            $t->dropIndex(['publication_control']);
            $t->dropColumn('publication_control');
        });
    }
};
