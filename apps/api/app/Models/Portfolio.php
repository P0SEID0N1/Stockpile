<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'base_currency',
        'benchmark_symbol',
        'benchmark_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function allocationTargets(): HasMany
    {
        return $this->hasMany(AllocationTarget::class);
    }

    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class);
    }
}
