<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Digest extends Model
{
    protected $fillable = [
        'user_id',
        'item_ids',
        'channel',
        'sent_at',
    ];

    protected $casts = [
        'item_ids' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
