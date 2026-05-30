<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenchmarkPriceHistory extends Model
{
    protected $fillable = [
        'symbol',
        'provider_symbol',
        'label',
        'price_date',
        'close_price',
        'source',
    ];

    protected $casts = [
        'price_date' => 'date',
        'close_price' => 'decimal:6',
    ];
}
