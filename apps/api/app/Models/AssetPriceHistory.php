<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetPriceHistory extends Model
{
    protected $fillable = [
        'asset_id',
        'price_date',
        'open_price',
        'high_price',
        'low_price',
        'close_price',
        'adj_close_price',
        'dividend_cash',
        'split_factor',
        'source',
    ];

    protected $casts = [
        'price_date' => 'date',
        'open_price' => 'decimal:6',
        'high_price' => 'decimal:6',
        'low_price' => 'decimal:6',
        'close_price' => 'decimal:6',
        'adj_close_price' => 'decimal:6',
        'dividend_cash' => 'decimal:6',
        'split_factor' => 'decimal:6',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
