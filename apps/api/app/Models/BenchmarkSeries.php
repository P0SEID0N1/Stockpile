<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenchmarkSeries extends Model
{
    protected $fillable = [
        'portfolio_id',
        'symbol',
        'label',
        'series_date',
        'close_price',
        'source',
    ];

    protected $casts = [
        'series_date' => 'date',
        'close_price' => 'decimal:6',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
