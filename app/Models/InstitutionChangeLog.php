<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionChangeLog extends Model
{
    protected $fillable = [
        'institution_id', 'changed_by', 'change_type',
        'old_values', 'new_values', 'source_reference',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(School::class, 'institution_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function log(int $institutionId, string $type, ?array $old = null, ?array $new = null, ?int $userId = null, ?string $source = null): void
    {
        try {
            static::create([
                'institution_id' => $institutionId,
                'changed_by' => $userId ?? auth()->id(),
                'change_type' => $type,
                'old_values' => $old,
                'new_values' => $new,
                'source_reference' => $source,
            ]);
        } catch (\Throwable $e) {
            // Jangan gagalkan operasi utama jika log gagal
            \Illuminate\Support\Facades\Log::warning('Change log gagal: ' . $e->getMessage());
        }
    }
}
