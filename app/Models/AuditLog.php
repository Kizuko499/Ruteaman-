<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit tidak dapat diubah.'));
        static::deleting(fn () => throw new \LogicException('Audit tidak dapat dihapus.'));
    }

    public function newEloquentBuilder($query)
    {
        return new class($query) extends Builder
        {
            public function update(array $values)
            {
                throw new \LogicException('Audit tidak dapat diubah.');
            }

            public function delete()
            {
                throw new \LogicException('Audit tidak dapat dihapus.');
            }

            public function forceDelete()
            {
                throw new \LogicException('Audit tidak dapat dihapus.');
            }
        };
    }

    protected function casts(): array
    {
        return ['meta_json' => 'array', 'changes' => 'array', 'meta' => 'array', 'created_at' => 'datetime', 'occurred_at' => 'datetime'];
    }
}
