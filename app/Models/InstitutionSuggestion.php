<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionSuggestion extends Model
{
    protected $fillable = [
        'ticket_number', 'category', 'institution_id', 'proposed_name', 'npsn',
        'province', 'regency', 'address', 'latitude', 'longitude',
        'description', 'source_url', 'contact_email',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(School::class, 'institution_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function categories(): array
    {
        return [
            'new_school' => 'Institusi belum terdaftar',
            'wrong_name' => 'Nama institusi salah',
            'wrong_address' => 'Alamat tidak sesuai',
            'wrong_location' => 'Pin lokasi salah tempat',
            'closed' => 'Institusi sudah tutup atau berubah status',
            'duplicate' => 'Institusi duplikat',
            'other' => 'Lainnya',
        ];
    }

    public static function statuses(): array
    {
        return [
            'new' => 'Baru',
            'reviewing' => 'Dalam peninjauan',
            'waiting_info' => 'Menunggu info tambahan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'done' => 'Selesai',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $s) {
            if (empty($s->ticket_number)) {
                $s->ticket_number = 'SRN-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }
}
