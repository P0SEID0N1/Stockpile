<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BenchmarkSeries;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\PriceQuote;
use App\Services\MarketData\MarketDataProvider;

class QuoteRefreshService
{
    public function __construct(
        private readonly MarketDataProvider $marketDataProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(?int $portfolioId = null): array
    {
        $assetQuery = Asset::query()->whereNotNull('symbol');
        if ($portfolioId) {
            $assetQuery->whereHas('holdings.account', fn ($query) => $query->where('portfolio_id', $portfolioId));
        }

        $assets = $assetQuery->get();
        $quotes = $this->marketDataProvider->fetchQuotes(
            $assets->pluck('symbol')->filter()->unique()->values()->all(),
        );

        $refreshed = 0;
        foreach ($assets as $asset) {
            $quote = $quotes[$asset->symbol] ?? null;
            if (! $quote) {
                continue;
            }

            PriceQuote::query()->create([
                'asset_id' => $asset->id,
                'price' => $quote['price'],
                'currency' => $quote['currency'],
                'price_date' => $quote['price_date'],
                'quoted_at' => $quote['quoted_at'],
                'source' => $quote['source'],
                'day_change' => $quote['day_change'],
                'day_change_percent' => $quote['day_change_percent'],
            ]);

            Holding::query()
                ->where('asset_id', $asset->id)
                ->update([
                    'market_value' => \DB::raw('quantity * '.(float) $quote['price']),
                    'price_as_of' => $quote['quoted_at'],
                ]);

            $refreshed++;
        }

        $benchmarksRefreshed = 0;
        $portfolios = Portfolio::query()
            ->when($portfolioId, fn ($query) => $query->whereKey($portfolioId))
            ->get();

        foreach ($portfolios as $portfolio) {
            $benchmarkQuote = $quotes[$portfolio->benchmark_symbol] ?? $this->marketDataProvider->fetchQuotes([$portfolio->benchmark_symbol])[$portfolio->benchmark_symbol] ?? null;
            if (! $benchmarkQuote) {
                continue;
            }

            BenchmarkSeries::query()->updateOrCreate(
                [
                    'symbol' => $portfolio->benchmark_symbol,
                    'series_date' => today(),
                ],
                [
                    'portfolio_id' => $portfolio->id,
                    'label' => $portfolio->benchmark_name,
                    'close_price' => $benchmarkQuote['price'],
                    'source' => $benchmarkQuote['source'],
                ],
            );

            $benchmarksRefreshed++;
        }

        return [
            'quotes_refreshed' => $refreshed,
            'benchmarks_refreshed' => $benchmarksRefreshed,
            'provider' => class_basename($this->marketDataProvider),
        ];
    }
}
