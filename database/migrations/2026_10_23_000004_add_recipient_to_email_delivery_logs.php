<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_delivery_logs') && ! Schema::hasColumn('email_delivery_logs', 'notification_recipient_id')) {
            Schema::table('email_delivery_logs', function (Blueprint $table) {
                $table->foreignId('notification_recipient_id')->nullable()->after('user_id')
                    ->constrained('notification_recipients')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_delivery_logs') && Schema::hasColumn('email_delivery_logs', 'notification_recipient_id')) {
            Schema::table('email_delivery_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('notification_recipient_id');
            });
        }
    }
};
