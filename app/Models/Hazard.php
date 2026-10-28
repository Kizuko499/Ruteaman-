<?php

namespace App\Models;

use App\Enums\HazardStatus;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Hazard extends Model
{
    protected $fillable = ['type_id', 'severity', 'status', 'confidence', 'first_reported_at', 'last_confirmed_at', 'resolved_at', 'public_slug', 'is_demo', 'location_label'];

    protected static function booted(): void
    {
        static::saving(function (self $h) {
            if (($h->publication_control ?? 'normal') === 'normal' && ! $h->requires_moderation && ! $h->published_at && in_array($h->status?->value, ['confirmed', 'verified', 'disputed', 'resolved'], true)) {
                $h->published_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return ['requires_moderation' => 'boolean', 'is_demo' => 'boolean', 'status' => HazardStatus::class, 'confidence' => 'float', 'first_reported_at' => 'datetime', 'last_confirmed_at' => 'datetime', 'resolved_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function scopePublicListed($q)
    {
        return $q->where('publication_control', 'normal')->where('requires_moderation', false)->where(fn ($v) => $v->whereIn('status', ['confirmed', 'verified', 'disputed', 'resolved'])->orWhere(fn ($n) => $n->where('status', 'new')->whereNotNull('published_at')))->whereRaw("COALESCE((SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1),'') != 'rejected'");
    }

    public function scopeActive($query)
    {
        $query->where('publication_control', 'normal')->whereIn('status', ['new', 'confirmed', 'verified', 'disputed']);
        if (DB::getDriverName() === 'pgsql') {
            return $query->whereRaw("COALESCE(hazards.last_confirmed_at,hazards.first_reported_at) > NOW() - (SELECT ttl_minutes * 3 * INTERVAL '1 minute' FROM hazard_types WHERE id=hazards.type_id)");
        }

        return $query->whereRaw('TIMESTAMPDIFF(MINUTE,COALESCE(hazards.last_confirmed_at,hazards.first_reported_at),NOW()) < (SELECT ttl_minutes * 3 FROM hazard_types WHERE id=hazards.type_id)');
    }

    public function scopeInSchool($query, int $schoolId)
    {
        $q = $query->whereRaw('EXISTS (SELECT 1 FROM schools s WHERE s.id = ? AND ST_Intersects(s.catchment,hazards.location))', [$schoolId]);
        $s = School::find($schoolId);
        if ($s && ! app(PermissionService::class)->feature($s, 'shared_layer')) {
            $q->whereHas('reports', fn ($r) => $r->where(fn ($r) => $r->whereHas('reporter', fn ($u) => $u->where('school_id', $schoolId))->orWhere('school_id', $schoolId)));
        }

        return $q;
    }

    public function getStatusLabelAttribute(): string
    {
        if (($this->publication_control ?? 'normal') !== 'normal') {
            return $this->publication_control === 'removed' ? __('Dihapus dari publik') : __('Ditangguhkan pengelola');
        }if ($this->requires_moderation && $this->status->value !== 'resolved') {
            return __('Menunggu verifikasi pengelola');
        }

        return __(match ($this->status->value) {
            'new' => $this->published_at ? 'Belum terverifikasi' : 'Menunggu konfirmasi','confirmed' => 'Dikonfirmasi Komunitas','verified' => 'Terverifikasi','disputed' => 'Perlu ditinjau','resolved' => 'Selesai','expired' => 'Kedaluwarsa'
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(HazardType::class, 'type_id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_slug';
    }

    public function reports(): HasMany
    {
        return $this->hasMany(HazardReport::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(HazardComment::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(HazardConfirmation::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(Escalation::class);
    }
}
