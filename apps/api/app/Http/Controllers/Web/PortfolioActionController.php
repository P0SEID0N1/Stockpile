<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Services\PortfolioLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortfolioActionController extends Controller
{
    use InteractsWithPortfolio;

    public function storeHolding(Request $request, PortfolioLedgerService $portfolioLedgerService): RedirectResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'asset_type' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'trade_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'purchase_price' => ['required', 'numeric', 'gt:0'],
            'total_cost' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $expectedTotal = round((float) $validated['purchase_price'] * (float) $validated['quantity'], 2);

        if (abs($expectedTotal - (float) $validated['total_cost']) > 0.02) {
            return back()
                ->withErrors(['total_cost' => 'Total cost must match price multiplied by quantity.'])
                ->withInput();
        }

        $portfolioLedgerService->recordBuy($portfolio, [
            ...$validated,
            'origin' => 'web-portfolio',
        ]);

        return redirect()
            ->route('portfolio.index')
            ->with('status', 'Stock purchase added and portfolio history rebuilt.');
    }

    public function reset(Request $request, PortfolioLedgerService $portfolioLedgerService): RedirectResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $replacement = $portfolioLedgerService->resetPortfolio($portfolio);

        return redirect()
            ->route('settings.index', ['portfolio_id' => $replacement->id])
            ->with('status', 'Portfolio wiped and recreated successfully.');
    }
}
