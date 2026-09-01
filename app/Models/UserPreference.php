<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'keywords',
        'digest_frequency',
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    protected $attributes = [
        'digest_frequency' => 'daily',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
