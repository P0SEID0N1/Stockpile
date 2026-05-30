<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\PriceQuote;
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

    public function store(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'account_id' => ['required', 'integer'],
            'asset_id' => ['nullable', 'integer'],
            'symbol' => ['required_without:asset_id', 'string', 'max:20'],
            'name' => ['required_without:asset_id', 'string', 'max:255'],
            'asset_type' => ['required_without:asset_id', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'quantity' => ['required', 'numeric'],
            'cost_basis_total' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $account = $portfolio->accounts()->whereKey($validated['account_id'])->firstOrFail();
        $asset = isset($validated['asset_id'])
            ? Asset::query()->findOrFail($validated['asset_id'])
            : Asset::query()->firstOrCreate(
                [
                    'symbol' => strtoupper($validated['symbol']),
                    'asset_type' => strtolower($validated['asset_type']),
                ],
                [
                    'name' => $validated['name'],
                    'currency' => strtoupper($validated['currency'] ?? $portfolio->base_currency),
                ],
            );

        $holding = Holding::query()->updateOrCreate(
            [
                'account_id' => $account->id,
                'asset_id' => $asset->id,
            ],
            [
                'quantity' => $validated['quantity'],
                'cost_basis_total' => $validated['cost_basis_total'],
                'notes' => $validated['notes'] ?? null,
                'market_value' => $validated['cost_basis_total'],
            ],
        );

        return response()->json($holding->load(['account', 'asset']), 201);
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
            'quantity' => ['sometimes', 'numeric'],
            'cost_basis_total' => ['sometimes', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $holding->fill($validated)->save();

        return response()->json($holding->refresh()->load(['account', 'asset']));
    }
}
