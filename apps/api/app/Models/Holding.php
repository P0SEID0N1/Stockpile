<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Holding extends Model
{
    protected $fillable = [
        'account_id',
        'asset_id',
        'quantity',
        'cost_basis_total',
        'market_value',
        'price_as_of',
        'last_snapshot_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'cost_basis_total' => 'decimal:2',
            'market_value' => 'decimal:2',
            'price_as_of' => 'datetime',
            'last_snapshot_at' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(HoldingSnapshot::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
