<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceQuote extends Model
{
    protected $fillable = [
        'asset_id',
        'price',
        'currency',
        'price_date',
        'quoted_at',
        'source',
        'day_change',
        'day_change_percent',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'price_date' => 'date',
        'quoted_at' => 'datetime',
        'day_change' => 'decimal:6',
        'day_change_percent' => 'decimal:4',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
