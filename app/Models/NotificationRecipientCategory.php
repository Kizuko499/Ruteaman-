<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecipientCategory extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(NotificationRecipient::class, 'recipient_id');
    }
}
