<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetPriceHistory;
use App\Models\BenchmarkPriceHistory;
use App\Models\Portfolio;
use App\Services\MarketData\MarketDataProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MarketHistoryService
{
    public function __construct(
        private readonly MarketDataProvider $marketDataProvider,
    ) {
    }

    /**
     * @return Collection<int, AssetPriceHistory>
     */
    public function syncAssetHistory(Asset $asset, Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        $rows = $this->marketDataProvider->fetchDailyHistory(
            symbol: (string) $asset->symbol,
            startDate: $startDate->toDateString(),
            endDate: ($endDate ?? today())->toDateString(),
        );

        if ($rows !== []) {
            $timestamp = now();
            AssetPriceHistory::query()->upsert(
                collect($rows)->map(fn (array $row) => [
                    'asset_id' => $asset->id,
                    'price_date' => $row['date'],
                    'open_price' => $row['open_price'],
                    'high_price' => $row['high_price'],
                    'low_price' => $row['low_price'],
                    'close_price' => $row['close_price'],
                    'adj_close_price' => $row['adj_close_price'],
                    'dividend_cash' => $row['dividend_cash'],
                    'split_factor' => $row['split_factor'],
                    'source' => $row['source'],
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ])->all(),
                ['asset_id', 'price_date'],
                ['open_price', 'high_price', 'low_price', 'close_price', 'adj_close_price', 'dividend_cash', 'split_factor', 'source', 'updated_at']
            );
        }

        return $asset->priceHistory()
            ->whereDate('price_date', '>=', $startDate->toDateString())
            ->orderBy('price_date')
            ->get();
    }

    /**
     * @return Collection<int, BenchmarkPriceHistory>
     */
    public function syncBenchmarkHistory(Portfolio $portfolio, Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        [$benchmarkSymbol, $providerSymbol, $benchmarkLabel] = $this->resolveBenchmark($portfolio);
        $rows = $this->marketDataProvider->fetchDailyHistory(
            symbol: $providerSymbol,
            startDate: $startDate->toDateString(),
            endDate: ($endDate ?? today())->toDateString(),
        );

        if ($rows === [] && $providerSymbol !== 'VTI') {
            $providerSymbol = 'VTI';
            $benchmarkSymbol = 'W5000';
            $benchmarkLabel = 'Wilshire 5000 (proxy: VTI)';
            $rows = $this->marketDataProvider->fetchDailyHistory(
                symbol: $providerSymbol,
                startDate: $startDate->toDateString(),
                endDate: ($endDate ?? today())->toDateString(),
            );
        }

        if ($rows !== []) {
            $timestamp = now();
            BenchmarkPriceHistory::query()->upsert(
                collect($rows)->map(fn (array $row) => [
                    'symbol' => $benchmarkSymbol,
                    'price_date' => $row['date'],
                    'provider_symbol' => $providerSymbol,
                    'label' => $benchmarkLabel,
                    'close_price' => $row['close_price'],
                    'source' => $row['source'],
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ])->all(),
                ['symbol', 'price_date'],
                ['provider_symbol', 'label', 'close_price', 'source', 'updated_at']
            );
        }

        return BenchmarkPriceHistory::query()
            ->where('symbol', $benchmarkSymbol)
            ->whereDate('price_date', '>=', $startDate->toDateString())
            ->orderBy('price_date')
            ->get();
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function resolveBenchmark(Portfolio $portfolio): array
    {
        $symbol = strtoupper((string) ($portfolio->benchmark_symbol ?: 'W5000'));

        if (in_array($symbol, ['W5000', 'WILSHIRE5000'], true)) {
            return ['W5000', 'W5000', 'Wilshire 5000'];
        }

        return [$symbol, $symbol, $portfolio->benchmark_name ?: $symbol];
    }
}
