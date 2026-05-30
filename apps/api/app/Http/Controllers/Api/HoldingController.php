<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\HoldingSnapshot;
use App\Models\JournalEntry;
use App\Models\PriceQuote;
use App\Services\QuoteRefreshService;
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

    public function store(Request $request, QuoteRefreshService $quoteRefreshService): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'asset_id' => ['nullable', 'integer'],
            'symbol' => ['required_without:asset_id', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'asset_type' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'quantity' => ['required', 'numeric'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'cost_basis_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $quantity = (float) $validated['quantity'];
        $purchasePrice = isset($validated['purchase_price']) ? (float) $validated['purchase_price'] : null;
        $costBasisTotal = isset($validated['cost_basis_total'])
            ? (float) $validated['cost_basis_total']
            : round(($purchasePrice ?? 0) * $quantity, 2);

        abort_if($purchasePrice === null && ! isset($validated['cost_basis_total']), 422, 'Either purchase_price or cost_basis_total is required.');

        $account = $this->resolveAccount($portfolio, $validated);
        $asset = isset($validated['asset_id'])
            ? Asset::query()->findOrFail($validated['asset_id'])
            : Asset::query()->firstOrCreate(
                [
                    'symbol' => strtoupper($validated['symbol']),
                    'asset_type' => strtolower($validated['asset_type'] ?? 'stocks'),
                ],
                [
                    'name' => $validated['name'] ?? strtoupper($validated['symbol']),
                    'currency' => strtoupper($validated['currency'] ?? $portfolio->base_currency),
                ],
            );

        $holding = Holding::query()->updateOrCreate(
            [
                'account_id' => $account->id,
                'asset_id' => $asset->id,
            ],
            [
                'quantity' => $quantity,
                'cost_basis_total' => $costBasisTotal,
                'notes' => $validated['notes'] ?? null,
                'market_value' => $costBasisTotal,
                'last_snapshot_at' => today()->toDateString(),
            ],
        );

        HoldingSnapshot::query()->updateOrCreate(
            [
                'holding_id' => $holding->id,
                'snapshot_date' => today()->toDateString(),
            ],
            [
                'quantity' => $quantity,
                'cost_basis_total' => $costBasisTotal,
                'market_value' => $costBasisTotal,
                'price_per_unit' => $quantity > 0 ? round($costBasisTotal / $quantity, 6) : null,
                'source_type' => 'manual',
                'source_reference' => 'apple-app',
            ],
        );

        JournalEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'account_id' => $account->id,
            'holding_id' => $holding->id,
            'entry_type' => 'buy',
            'trade_date' => today()->toDateString(),
            'quantity' => $quantity,
            'price_per_unit' => $purchasePrice ?? ($quantity > 0 ? $costBasisTotal / $quantity : null),
            'amount' => $costBasisTotal,
            'notes' => $validated['notes'] ?? 'Manual holding entry',
        ]);

        $quoteRefreshService->refresh($portfolio->id);

        return response()->json($holding->fresh()->load(['account', 'asset']), 201);
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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveAccount(\App\Models\Portfolio $portfolio, array $validated): Account
    {
        if (isset($validated['account_id'])) {
            return $portfolio->accounts()->whereKey($validated['account_id'])->firstOrFail();
        }

        if (! empty($validated['account_name'])) {
            return $portfolio->accounts()->firstOrCreate(
                ['name' => $validated['account_name']],
                [
                    'type' => $validated['account_type'] ?? 'taxable',
                    'currency' => strtoupper($validated['currency'] ?? $portfolio->base_currency),
                ],
            );
        }

        return $portfolio->accounts()->firstOrCreate(
            ['name' => 'Primary Brokerage'],
            [
                'type' => 'taxable',
                'currency' => $portfolio->base_currency,
            ],
        );
    }
}
