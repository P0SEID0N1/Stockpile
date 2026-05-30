<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = [
        'symbol',
        'name',
        'asset_type',
        'currency',
        'exchange',
        'sector',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    public function priceQuotes(): HasMany
    {
        return $this->hasMany(PriceQuote::class);
    }
}
