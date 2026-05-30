<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Portfolio;
use Illuminate\Http\Request;

trait InteractsWithPortfolio
{
    protected function resolvePortfolio(Request $request): Portfolio
    {
        $user = $request->user();
        $portfolioId = $request->integer('portfolio_id') ?: $request->integer('portfolioId');

        $query = $user->portfolios()->orderBy('id');

        if ($portfolioId) {
            return $query->whereKey($portfolioId)->firstOrFail();
        }

        return $query->firstOrFail();
    }
}
