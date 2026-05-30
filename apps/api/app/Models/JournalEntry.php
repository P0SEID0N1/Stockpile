<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JournalEntry extends Model
{
    protected $fillable = [
        'portfolio_id',
        'account_id',
        'asset_id',
        'holding_id',
        'entry_type',
        'trade_date',
        'quantity',
        'price_per_unit',
        'amount',
        'source_type',
        'linked_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'quantity' => 'decimal:6',
        'price_per_unit' => 'decimal:6',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }

    public function linkedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_entry_id');
    }

    public function reinvestmentEntry(): HasOne
    {
        return $this->hasOne(self::class, 'linked_entry_id');
    }
}
