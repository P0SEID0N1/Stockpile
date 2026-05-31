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
            'purchase_price' => ['nullable', 'numeric', 'gt:0', 'required_without:total_cost'],
            'total_cost' => ['nullable', 'numeric', 'gt:0', 'required_without:purchase_price'],
            'notes' => ['nullable', 'string'],
        ]);
        $quantity = (float) $validated['quantity'];
        $purchasePrice = isset($validated['purchase_price']) ? (float) $validated['purchase_price'] : null;
        $totalCost = isset($validated['total_cost'])
            ? round((float) $validated['total_cost'], 2)
            : round(((float) $purchasePrice) * $quantity, 2);

        $portfolioLedgerService->recordBuy($portfolio, [
            ...$validated,
            'purchase_price' => $purchasePrice ?? round($totalCost / $quantity, 6),
            'total_cost' => $totalCost,
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
