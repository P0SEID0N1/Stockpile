<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationTarget extends Model
{
    protected $fillable = [
        'portfolio_id',
        'account_id',
        'asset_id',
        'asset_type',
        'label',
        'target_percentage',
    ];

    protected $casts = [
        'target_percentage' => 'decimal:4',
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
}
