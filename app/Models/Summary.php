<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Summary extends Model
{
    protected $fillable = [
        'item_id',
        'summary_text',
        'tags',
        'relevance_score',
        'model_used',
    ];

    protected $casts = [
        'tags' => 'array',
        'relevance_score' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
