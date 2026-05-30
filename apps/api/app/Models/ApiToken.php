<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function issueFor(User $user, string $name = 'device'): array
    {
        $plainTextToken = Str::random(64);
        $token = $user->apiTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(90),
        ]);

        return [$token, $plainTextToken];
    }
}
