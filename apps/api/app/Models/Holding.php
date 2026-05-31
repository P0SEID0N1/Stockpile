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

    protected $casts = [
        'quantity' => 'decimal:6',
        'cost_basis_total' => 'decimal:2',
        'market_value' => 'decimal:2',
        'price_as_of' => 'datetime',
        'last_snapshot_at' => 'date',
    ];

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

    public function averageCostPerShare(): float
    {
        $quantity = (float) $this->quantity;

        return $quantity > 0 ? round((float) $this->cost_basis_total / $quantity, 6) : 0.0;
    }

    public function manualNetInvestedTotal(): float
    {
        $entries = $this->relationLoaded('journalEntries')
            ? $this->journalEntries->where('source_type', 'manual')
            : $this->journalEntries()->where('source_type', 'manual')->get();

        return round($entries->reduce(function (float $carry, JournalEntry $entry) {
            return $carry + match ($entry->entry_type) {
                'buy' => (float) ($entry->amount ?? 0),
                'sell' => -1 * (float) ($entry->amount ?? 0),
                default => 0.0,
            };
        }, 0.0), 2);
    }

    public function dripBasisAdjustment(): float
    {
        return round((float) $this->cost_basis_total - $this->manualNetInvestedTotal(), 2);
    }

    public function currentPricePerShare(): float
    {
        $quantity = (float) $this->quantity;

        return $quantity > 0 ? round((float) $this->market_value / $quantity, 6) : 0.0;
    }

    public function trailingDividendYieldPercent(): ?float
    {
        $currentPrice = $this->currentPricePerShare();

        if ($currentPrice <= 0) {
            return null;
        }

        $history = $this->asset->relationLoaded('priceHistory')
            ? $this->asset->priceHistory
            : $this->asset->priceHistory()
                ->whereDate('price_date', '>=', now()->subYear()->toDateString())
                ->get();

        $annualDividendPerShare = round((float) $history
            ->where('price_date', '>=', now()->subYear())
            ->sum(fn (AssetPriceHistory $row) => (float) ($row->dividend_cash ?? 0)), 6);

        if ($annualDividendPerShare <= 0) {
            return null;
        }

        return round(($annualDividendPerShare / $currentPrice) * 100, 2);
    }
}
