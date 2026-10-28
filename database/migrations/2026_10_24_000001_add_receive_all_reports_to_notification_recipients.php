<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_recipients') && ! Schema::hasColumn('notification_recipients', 'receive_all_reports')) {
            Schema::table('notification_recipients', function (Blueprint $table) {
                $table->boolean('receive_all_reports')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_recipients') && Schema::hasColumn('notification_recipients', 'receive_all_reports')) {
            Schema::table('notification_recipients', function (Blueprint $table) {
                $table->dropColumn('receive_all_reports');
            });
        }
    }
};
