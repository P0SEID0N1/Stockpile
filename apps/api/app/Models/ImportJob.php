<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    protected $fillable = [
        'user_id',
        'portfolio_id',
        'filename',
        'status',
        'preview_payload',
        'result_payload',
        'imported_rows',
        'failed_rows',
        'committed_at',
    ];

    protected $casts = [
        'preview_payload' => 'array',
        'result_payload' => 'array',
        'committed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
