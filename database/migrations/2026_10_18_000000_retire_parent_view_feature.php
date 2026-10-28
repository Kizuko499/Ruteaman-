<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('schools')->select('id')->whereNotNull('features')->orderBy('id')->chunkById(100, function ($schools) {
            DB::transaction(function () use ($schools) {
                foreach ($schools as $item) {
                    $row = DB::table('schools')->where('id', $item->id)->lockForUpdate()->first();
                    $features = json_decode($row?->features ?? '', true);
                    if (! is_array($features) || ! array_key_exists('parent_view', $features)) {
                        continue;
                    }
                    unset($features['parent_view']);
                    DB::table('schools')->where('id', $item->id)->update(['features' => json_encode((object) $features, JSON_THROW_ON_ERROR)]);
                }
            });
        });
    }

    public function down(): void
    {
        // Retired feature values cannot be reconstructed. Restore a consistent backup for a rollback.
    }
};
