<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HazardComment extends Model
{
    protected $fillable = ['hazard_id', 'user_id', 'body'];

    public function hazard()
    {
        return $this->belongsTo(Hazard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Nama penulis: nama akun bila login, "Guest" bila anonim. */
    public function authorName(): string
    {
        return $this->user?->display_name ?: 'Guest';
    }
}
