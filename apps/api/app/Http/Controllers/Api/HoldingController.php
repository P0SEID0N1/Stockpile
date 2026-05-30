<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Holding;
use App\Models\PriceQuote;
use App\Services\PortfolioLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HoldingController extends Controller
{
    use InteractsWithPortfolio;

    public function index(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        $holdings = Holding::query()
            ->with(['account', 'asset'])
            ->whereHas('account', fn ($query) => $query->where('portfolio_id', $portfolio->id))
            ->when($request->filled('account_id'), fn ($query) => $query->where('account_id', $request->integer('account_id')))
            ->orderByDesc('market_value')
            ->get();

        $quoteTimestamp = PriceQuote::query()->latest('quoted_at')->value('quoted_at');

        return response()->json([
            'quote_timestamp' => $quoteTimestamp,
            'data' => $holdings,
        ]);
    }

    public function store(Request $request, PortfolioLedgerService $portfolioLedgerService): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'symbol' => ['required_without:asset_id', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'asset_type' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'trade_date' => ['nullable', 'date'],
            'quantity' => ['required', 'numeric'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $expectedTotal = round((float) $validated['purchase_price'] * (float) $validated['quantity'], 2);
        $totalCost = isset($validated['total_cost']) ? (float) $validated['total_cost'] : $expectedTotal;
        abort_if(abs($expectedTotal - $totalCost) > 0.02, 422, 'Total cost must match price multiplied by quantity.');

        $entry = $portfolioLedgerService->recordBuy($portfolio, [
            ...$validated,
            'trade_date' => $validated['trade_date'] ?? today()->toDateString(),
            'total_cost' => $totalCost,
            'origin' => 'api-holdings',
        ]);
        $holding = Holding::query()
            ->with(['account', 'asset'])
            ->where('account_id', $entry->account_id)
            ->where('asset_id', $entry->asset_id)
            ->first();

        if (! $holding) {
            return response()->json($entry->load(['account', 'asset', 'holding.asset']), 201);
        }

        return response()->json($holding, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $holding = Holding::query()
            ->with(['account', 'asset'])
            ->whereHas('account', fn ($query) => $query->where('portfolio_id', $portfolio->id))
            ->whereKey($id)
            ->firstOrFail();

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $holding->fill($validated)->save();

        return response()->json($holding->refresh()->load(['account', 'asset']));
    }
}
