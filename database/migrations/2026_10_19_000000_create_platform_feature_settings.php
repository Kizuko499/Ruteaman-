<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_feature_settings', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->json('features');
            $t->timestamp('updated_at')->nullable();
        });
        DB::table('platform_feature_settings')->insert(['id' => 1, 'features' => json_encode(config('ruteaman_admin.features'), JSON_THROW_ON_ERROR), 'updated_at' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_feature_settings');
    }
};
