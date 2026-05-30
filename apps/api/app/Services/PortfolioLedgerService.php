<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\HoldingSnapshot;
use App\Models\JournalEntry;
use App\Models\Portfolio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PortfolioLedgerService
{
    public function __construct(
        private readonly MarketHistoryService $marketHistoryService,
        private readonly QuoteRefreshService $quoteRefreshService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordBuy(Portfolio $portfolio, array $payload): JournalEntry
    {
        $account = $this->resolveAccount($portfolio, $payload);
        $asset = $this->resolveAsset($portfolio, $payload);
        $tradeDate = Carbon::parse((string) $payload['trade_date'])->toDateString();
        $quantity = round((float) $payload['quantity'], 6);
        $totalCost = round((float) ($payload['total_cost'] ?? $payload['cost_basis_total']), 2);
        $pricePerUnit = round((float) ($payload['purchase_price'] ?? ($quantity > 0 ? $totalCost / $quantity : 0)), 6);

        $holding = Holding::query()->firstOrCreate(
            [
                'account_id' => $account->id,
                'asset_id' => $asset->id,
            ],
            [
                'quantity' => 0,
                'cost_basis_total' => 0,
                'market_value' => 0,
            ],
        );

        $entry = $portfolio->journalEntries()->create([
            'account_id' => $account->id,
            'asset_id' => $asset->id,
            'holding_id' => $holding->id,
            'entry_type' => 'buy',
            'trade_date' => $tradeDate,
            'quantity' => $quantity,
            'price_per_unit' => $pricePerUnit,
            'amount' => $totalCost,
            'source_type' => 'manual',
            'notes' => $payload['notes'] ?? 'Manual stock purchase',
            'metadata' => [
                'origin' => $payload['origin'] ?? 'manual',
            ],
        ]);

        $this->rebuildPortfolio($portfolio);

        return $entry->refresh()->load(['account', 'asset', 'holding.asset', 'reinvestmentEntry']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordManualTransaction(Portfolio $portfolio, array $payload): JournalEntry
    {
        $asset = isset($payload['asset_id'])
            ? Asset::query()->findOrFail($payload['asset_id'])
            : $this->resolveAsset($portfolio, $payload);
        $account = isset($payload['account_id']) || ! empty($payload['account_name'])
            ? $this->resolveAccount($portfolio, $payload)
            : null;
        $holding = $account && $asset
            ? Holding::query()->firstOrCreate(
                [
                    'account_id' => $account->id,
                    'asset_id' => $asset->id,
                ],
                [
                    'quantity' => 0,
                    'cost_basis_total' => 0,
                    'market_value' => 0,
                ],
            )
            : null;

        $entry = $portfolio->journalEntries()->create([
            'account_id' => $account?->id,
            'asset_id' => $asset->id,
            'holding_id' => $holding?->id,
            'entry_type' => $payload['entry_type'],
            'trade_date' => Carbon::parse((string) $payload['trade_date'])->toDateString(),
            'quantity' => isset($payload['quantity']) ? round((float) $payload['quantity'], 6) : null,
            'price_per_unit' => isset($payload['price_per_unit']) ? round((float) $payload['price_per_unit'], 6) : null,
            'amount' => isset($payload['amount']) ? round((float) $payload['amount'], 2) : null,
            'source_type' => 'manual',
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->rebuildPortfolio($portfolio);

        return $entry->refresh()->load(['account', 'asset', 'holding.asset', 'reinvestmentEntry']);
    }

    public function resetPortfolio(Portfolio $portfolio): Portfolio
    {
        return DB::transaction(function () use ($portfolio) {
            $attributes = [
                'name' => $portfolio->name,
                'base_currency' => $portfolio->base_currency,
                'benchmark_symbol' => $portfolio->benchmark_symbol,
                'benchmark_name' => $portfolio->benchmark_name,
            ];

            $user = $portfolio->user;
            $portfolio->delete();

            $replacement = $user->portfolios()->create($attributes);
            $replacement->accounts()->create([
                'name' => 'Primary Brokerage',
                'type' => 'taxable',
                'currency' => $replacement->base_currency,
            ]);

            return $replacement;
        });
    }

    public function rebuildPortfolio(Portfolio $portfolio): void
    {
        DB::transaction(function () use ($portfolio) {
            $manualEntries = $portfolio->journalEntries()
                ->with(['asset', 'account'])
                ->where('source_type', 'manual')
                ->orderBy('trade_date')
                ->orderBy('id')
                ->get();

            $portfolio->journalEntries()
                ->where('source_type', 'auto_dividend')
                ->delete();

            $holdingIds = Holding::query()
                ->whereHas('account', fn ($query) => $query->where('portfolio_id', $portfolio->id))
                ->pluck('id');

            HoldingSnapshot::query()->whereIn('holding_id', $holdingIds)->delete();

            if ($manualEntries->isEmpty()) {
                Holding::query()
                    ->whereHas('account', fn ($query) => $query->where('portfolio_id', $portfolio->id))
                    ->delete();

                return;
            }

            $earliestTradeDate = $manualEntries->min(fn (JournalEntry $entry) => $entry->trade_date->toDateString());
            $assetIds = $manualEntries->pluck('asset_id')->filter()->unique()->values();
            $assets = Asset::query()->whereIn('id', $assetIds)->get()->keyBy('id');
            $historyByAsset = [];

            foreach ($assets as $asset) {
                $historyByAsset[$asset->id] = $this->marketHistoryService
                    ->syncAssetHistory($asset, Carbon::parse($earliestTradeDate))
                    ->keyBy(fn ($row) => $row->price_date->toDateString());
            }

            $this->marketHistoryService->syncBenchmarkHistory($portfolio, Carbon::parse($earliestTradeDate));

            $manualByPair = $manualEntries->groupBy(fn (JournalEntry $entry) => $this->pairKey(
                (int) $entry->account_id,
                (int) $entry->asset_id,
            ));
            $computedKeys = [];
            $generatedEntries = [];

            foreach ($manualByPair as $pairKey => $entries) {
                /** @var Collection<int, JournalEntry> $entries */
                $accountId = (int) $entries->first()->account_id;
                $assetId = (int) $entries->first()->asset_id;
                $asset = $assets->get($assetId);

                if (! $asset) {
                    continue;
                }

                $historyRows = $historyByAsset[$assetId] ?? collect();
                $sortedEntries = $entries->sortBy([
                    fn (JournalEntry $entry) => $entry->trade_date->toDateString(),
                    fn (JournalEntry $entry) => $entry->id,
                ])->values();
                $historyRows = $historyRows->isEmpty()
                    ? $this->fallbackHistoryRows($sortedEntries->groupBy(fn (JournalEntry $entry) => $entry->trade_date->toDateString()))
                    : $historyRows;

                $holding = Holding::query()->firstOrCreate(
                    [
                        'account_id' => $accountId,
                        'asset_id' => $assetId,
                    ],
                    [
                        'quantity' => 0,
                        'cost_basis_total' => 0,
                        'market_value' => 0,
                    ],
                );

                JournalEntry::query()
                    ->whereIn('id', $entries->pluck('id'))
                    ->update(['holding_id' => $holding->id, 'asset_id' => $assetId, 'account_id' => $accountId]);

                $quantity = 0.0;
                $costBasis = 0.0;
                $latestSnapshotDate = null;
                $latestMarketValue = 0.0;
                $entryCursor = 0;

                foreach ($historyRows as $date => $row) {
                    while ($entryCursor < $sortedEntries->count() && $sortedEntries[$entryCursor]->trade_date->toDateString() < $date) {
                        [$quantity, $costBasis] = $this->applyEntry($sortedEntries[$entryCursor], $quantity, $costBasis);
                        $entryCursor++;
                    }

                    $dividendCash = (float) ($row->dividend_cash ?? 0);

                    if ($dividendCash > 0 && $quantity > 0) {
                        $dividendAmount = round($quantity * $dividendCash, 2);
                        $closePrice = (float) $row->close_price;
                        $reinvestedQuantity = $closePrice > 0 ? round($dividendAmount / $closePrice, 6) : 0.0;

                        if ($dividendAmount > 0 && $reinvestedQuantity > 0) {
                            $generatedEntries[$pairKey][] = [
                                'holding_id' => $holding->id,
                                'account_id' => $accountId,
                                'asset_id' => $assetId,
                                'trade_date' => $date,
                                'dividend_amount' => $dividendAmount,
                                'reinvested_quantity' => $reinvestedQuantity,
                                'price_per_unit' => round($closePrice, 6),
                            ];

                            $quantity = round($quantity + $reinvestedQuantity, 6);
                            $costBasis = round($costBasis + $dividendAmount, 2);
                        }
                    }

                    while ($entryCursor < $sortedEntries->count() && $sortedEntries[$entryCursor]->trade_date->toDateString() === $date) {
                        [$quantity, $costBasis] = $this->applyEntry($sortedEntries[$entryCursor], $quantity, $costBasis);
                        $entryCursor++;
                    }

                    if ($quantity <= 0) {
                        continue;
                    }

                    $closePrice = (float) $row->close_price;
                    $latestSnapshotDate = $date;
                    $latestMarketValue = round($quantity * $closePrice, 2);

                    HoldingSnapshot::query()->create([
                        'holding_id' => $holding->id,
                        'snapshot_date' => $date,
                        'quantity' => $quantity,
                        'cost_basis_total' => round($costBasis, 2),
                        'market_value' => $latestMarketValue,
                        'price_per_unit' => round($closePrice, 6),
                        'source_type' => 'ledger',
                        'source_reference' => 'portfolio-ledger',
                    ]);
                }

                if ($quantity > 0) {
                    $holding->fill([
                        'quantity' => round($quantity, 6),
                        'cost_basis_total' => round($costBasis, 2),
                        'market_value' => round($latestMarketValue, 2),
                        'last_snapshot_at' => $latestSnapshotDate,
                    ])->save();
                    $computedKeys[] = $pairKey;
                } else {
                    $holding->delete();
                }
            }

            Holding::query()
                ->whereHas('account', fn ($query) => $query->where('portfolio_id', $portfolio->id))
                ->get()
                ->reject(fn (Holding $holding) => in_array($this->pairKey($holding->account_id, $holding->asset_id), $computedKeys, true))
                ->each(fn (Holding $holding) => $holding->delete());

            foreach ($generatedEntries as $pairEntries) {
                foreach ($pairEntries as $row) {
                    $dividendEntry = $portfolio->journalEntries()->create([
                        'account_id' => $row['account_id'],
                        'asset_id' => $row['asset_id'],
                        'holding_id' => $row['holding_id'],
                        'entry_type' => 'dividend',
                        'trade_date' => $row['trade_date'],
                        'amount' => $row['dividend_amount'],
                        'source_type' => 'auto_dividend',
                        'notes' => 'Dividend detected from price history',
                        'metadata' => [
                            'drip_assumed' => true,
                        ],
                    ]);

                    $portfolio->journalEntries()->create([
                        'account_id' => $row['account_id'],
                        'asset_id' => $row['asset_id'],
                        'holding_id' => $row['holding_id'],
                        'entry_type' => 'dividend_reinvested',
                        'trade_date' => $row['trade_date'],
                        'quantity' => $row['reinvested_quantity'],
                        'price_per_unit' => $row['price_per_unit'],
                        'amount' => $row['dividend_amount'],
                        'source_type' => 'auto_dividend',
                        'linked_entry_id' => $dividendEntry->id,
                        'notes' => 'Dividend automatically reinvested',
                        'metadata' => [
                            'drip_assumed' => true,
                        ],
                    ]);
                }
            }

            $this->quoteRefreshService->refresh($portfolio->id);
        });
    }

    private function pairKey(int $accountId, int $assetId): string
    {
        return $accountId.':'.$assetId;
    }

    /**
     * @param  Collection<string, Collection<int, JournalEntry>>  $entriesByDate
     * @return Collection<string, object>
     */
    private function fallbackHistoryRows(Collection $entriesByDate): Collection
    {
        $lastPrice = 0.0;

        return $entriesByDate
            ->map(function (Collection $entries, string $date) use (&$lastPrice) {
                $lastTrade = $entries->last();
                $price = (float) ($lastTrade?->price_per_unit ?: ($lastTrade?->amount && $lastTrade?->quantity
                    ? (float) $lastTrade->amount / max((float) $lastTrade->quantity, 0.01)
                    : $lastPrice));
                $lastPrice = $price > 0 ? $price : max($lastPrice, 0.01);

                return (object) [
                    'price_date' => Carbon::parse($date),
                    'close_price' => round(max($lastPrice, 0.01), 6),
                    'dividend_cash' => 0,
                ];
            })
            ->sortKeys()
            ->values()
            ->keyBy(fn ($row) => $row->price_date->toDateString());
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function applyEntry(JournalEntry $entry, float $quantity, float $costBasis): array
    {
        $entryQuantity = round((float) ($entry->quantity ?? 0), 6);
        $entryAmount = round((float) ($entry->amount ?? 0), 2);

        return match ($entry->entry_type) {
            'buy' => [
                round($quantity + $entryQuantity, 6),
                round($costBasis + $entryAmount, 2),
            ],
            'sell' => $this->applySell($quantity, $costBasis, $entryQuantity),
            'dividend_reinvested' => [
                round($quantity + $entryQuantity, 6),
                round($costBasis + $entryAmount, 2),
            ],
            default => [$quantity, $costBasis],
        };
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function applySell(float $quantity, float $costBasis, float $sellQuantity): array
    {
        $sellQuantity = min($sellQuantity, $quantity);
        $averageCost = $quantity > 0 ? $costBasis / $quantity : 0.0;
        $newQuantity = round(max($quantity - $sellQuantity, 0), 6);
        $newCostBasis = round(max($costBasis - ($averageCost * $sellQuantity), 0), 2);

        return [$newQuantity, $newCostBasis];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveAccount(Portfolio $portfolio, array $payload): Account
    {
        if (isset($payload['account_id'])) {
            return $portfolio->accounts()->whereKey($payload['account_id'])->firstOrFail();
        }

        if (! empty($payload['account_name'])) {
            return $portfolio->accounts()->firstOrCreate(
                ['name' => $payload['account_name']],
                [
                    'type' => $payload['account_type'] ?? 'taxable',
                    'currency' => strtoupper($payload['currency'] ?? $portfolio->base_currency),
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveAsset(Portfolio $portfolio, array $payload): Asset
    {
        if (isset($payload['asset_id'])) {
            return Asset::query()->findOrFail($payload['asset_id']);
        }

        $symbol = strtoupper((string) $payload['symbol']);

        return Asset::query()->firstOrCreate(
            [
                'symbol' => $symbol,
                'asset_type' => strtolower((string) ($payload['asset_type'] ?? 'stocks')),
            ],
            [
                'name' => $payload['name'] ?? $symbol,
                'currency' => strtoupper((string) ($payload['currency'] ?? $portfolio->base_currency)),
            ],
        );
    }
}
