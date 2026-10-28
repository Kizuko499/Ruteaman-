<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('npsn', 16)->nullable()->unique()->after('name');
            $table->string('education_level', 30)->nullable()->after('npsn');
            $table->string('school_status', 30)->nullable()->after('education_level');
            $table->string('province_code', 10)->nullable()->index()->after('school_status');
            $table->string('province_name')->nullable()->after('province_code');
            $table->string('regency_code', 10)->nullable()->index()->after('province_name');
            $table->string('regency_name')->nullable()->after('regency_code');
            $table->string('district_name')->nullable()->after('regency_name');
            $table->text('address')->nullable()->after('district_name');
            $table->timestamp('directory_synced_at')->nullable()->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropUnique(['npsn']);
            $table->dropIndex(['province_code']);
            $table->dropIndex(['regency_code']);
            $table->dropColumn([
                'npsn', 'education_level', 'school_status', 'province_code', 'province_name',
                'regency_code', 'regency_name', 'district_name', 'address', 'directory_synced_at',
            ]);
        });
    }
};