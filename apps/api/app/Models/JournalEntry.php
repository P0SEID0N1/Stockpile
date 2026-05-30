<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    protected $fillable = [
        'portfolio_id',
        'account_id',
        'holding_id',
        'entry_type',
        'trade_date',
        'quantity',
        'price_per_unit',
        'amount',
        'notes',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'quantity' => 'decimal:6',
        'price_per_unit' => 'decimal:6',
        'amount' => 'decimal:2',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }
}
