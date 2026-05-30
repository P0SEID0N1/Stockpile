<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoldingSnapshot extends Model
{
    protected $fillable = [
        'holding_id',
        'import_job_id',
        'snapshot_date',
        'quantity',
        'cost_basis_total',
        'market_value',
        'price_per_unit',
        'source_type',
        'source_reference',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'quantity' => 'decimal:6',
        'cost_basis_total' => 'decimal:2',
        'market_value' => 'decimal:2',
        'price_per_unit' => 'decimal:6',
    ];

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }
}
