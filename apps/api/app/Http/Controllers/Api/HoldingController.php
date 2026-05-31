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
            'purchase_price' => ['nullable', 'numeric', 'gt:0', 'required_without:total_cost'],
            'total_cost' => ['nullable', 'numeric', 'gt:0', 'required_without:purchase_price'],
            'notes' => ['nullable', 'string'],
        ]);

        $quantity = (float) $validated['quantity'];
        $purchasePrice = isset($validated['purchase_price']) ? (float) $validated['purchase_price'] : null;
        $totalCost = isset($validated['total_cost'])
            ? round((float) $validated['total_cost'], 2)
            : round(((float) $purchasePrice) * $quantity, 2);

        $entry = $portfolioLedgerService->recordBuy($portfolio, [
            ...$validated,
            'trade_date' => $validated['trade_date'] ?? today()->toDateString(),
            'purchase_price' => $purchasePrice ?? round($totalCost / max($quantity, 0.000001), 6),
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
