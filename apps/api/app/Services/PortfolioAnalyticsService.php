<?php

namespace App\Services;

use App\Models\BenchmarkPriceHistory;
use App\Models\Holding;
use App\Models\HoldingSnapshot;
use App\Models\Portfolio;
use App\Models\PriceQuote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PortfolioAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Portfolio $portfolio): array
    {
        $holdings = $this->portfolioHoldings($portfolio);
        $quotes = $this->latestQuotesForHoldings($holdings);
        [$benchmarkSymbol, , $benchmarkLabel] = app(MarketHistoryService::class)->resolveBenchmark($portfolio);

        $valueByAccount = [];
        $valueByAssetType = [];
        $currentValue = 0.0;
        $costBasis = 0.0;
        $dayChange = 0.0;

        foreach ($holdings as $holding) {
            $quote = $quotes->get($holding->asset_id);
            $quantity = (float) $holding->quantity;
            $marketValue = $quote
                ? $quantity * (float) $quote->price
                : (float) ($holding->market_value ?? $holding->cost_basis_total);
            $basis = (float) $holding->cost_basis_total;
            $delta = $quote ? $quantity * (float) ($quote->day_change ?? 0) : 0.0;

            $currentValue += $marketValue;
            $costBasis += $basis;
            $dayChange += $delta;

            $accountName = $holding->account->name;
            $assetType = $holding->asset->asset_type;
            $valueByAccount[$accountName] = ($valueByAccount[$accountName] ?? 0) + $marketValue;
            $valueByAssetType[$assetType] = ($valueByAssetType[$assetType] ?? 0) + $marketValue;
        }

        return [
            'portfolio_id' => $portfolio->id,
            'portfolio_name' => $portfolio->name,
            'benchmark_symbol' => $benchmarkSymbol,
            'benchmark_label' => $benchmarkLabel,
            'current_value' => round($currentValue, 2),
            'cost_basis_total' => round($costBasis, 2),
            'total_gain_loss' => round($currentValue - $costBasis, 2),
            'day_change' => round($dayChange, 2),
            'day_change_percent' => $currentValue > 0 ? round(($dayChange / max($currentValue - $dayChange, 0.01)) * 100, 2) : 0.0,
            'holdings_count' => $holdings->count(),
            'accounts_count' => $portfolio->accounts()->count(),
            'asset_type_allocation' => $this->percentBreakdown($valueByAssetType, $currentValue),
            'account_allocation' => $this->percentBreakdown($valueByAccount, $currentValue),
            'quote_timestamp' => $quotes->sortByDesc('quoted_at')->first()?->quoted_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function timeSeries(Portfolio $portfolio, string $range = '1m'): array
    {
        $points = HoldingSnapshot::query()
            ->selectRaw('holding_snapshots.snapshot_date, SUM(holding_snapshots.market_value) as total_value')
            ->join('holdings', 'holding_snapshots.holding_id', '=', 'holdings.id')
            ->join('accounts', 'holdings.account_id', '=', 'accounts.id')
            ->where('accounts.portfolio_id', $portfolio->id)
            ->groupBy('holding_snapshots.snapshot_date')
            ->orderBy('holding_snapshots.snapshot_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->snapshot_date instanceof Carbon ? $row->snapshot_date->toDateString() : (string) $row->snapshot_date,
                'value' => round((float) $row->total_value, 2),
            ]);

        $summary = $this->summary($portfolio);

        if ($points->isEmpty() || $points->last()['date'] !== today()->toDateString()) {
            $points->push([
                'date' => today()->toDateString(),
                'value' => $summary['current_value'],
            ]);
        }

        $rangedPoints = $this->applyRange($points, $range);
        $comparisonPortfolio = $this->normalizeSeries($rangedPoints);
        $benchmark = $this->benchmarkSeries($portfolio, $rangedPoints);
        $portfolioReturnPercent = $this->rangeReturn($rangedPoints);
        $benchmarkReturnPercent = $this->rangeReturn(collect($benchmark));

        return [
            'range' => $range,
            'portfolio' => $rangedPoints->values()->all(),
            'comparison_portfolio' => $comparisonPortfolio,
            'benchmark' => $benchmark,
            'portfolio_return_percent' => $portfolioReturnPercent,
            'benchmark_return_percent' => $benchmarkReturnPercent,
            'benchmark_symbol' => $summary['benchmark_symbol'],
            'benchmark_label' => $summary['benchmark_label'],
        ];
    }

    public function planner(Portfolio $portfolio): array
    {
        $summary = $this->summary($portfolio);
        $currentValue = (float) $summary['current_value'];
        $holdings = $this->portfolioHoldings($portfolio);
        $quotes = $this->latestQuotesForHoldings($holdings);
        $currentByType = [];

        foreach ($holdings as $holding) {
            $quote = $quotes->get($holding->asset_id);
            $marketValue = $quote
                ? (float) $holding->quantity * (float) $quote->price
                : (float) ($holding->market_value ?? $holding->cost_basis_total);

            $currentByType[$holding->asset->asset_type] = ($currentByType[$holding->asset->asset_type] ?? 0) + $marketValue;
        }

        $targets = $portfolio->allocationTargets()->with(['account', 'asset'])->get()->map(function ($target) use ($currentValue, $currentByType) {
            $currentBucketValue = $target->asset_type
                ? ($currentByType[$target->asset_type] ?? 0)
                : 0;
            $desiredValue = $currentValue * ((float) $target->target_percentage / 100);

            return [
                'id' => $target->id,
                'label' => $target->label ?: ($target->asset?->symbol ?: $target->asset_type ?: $target->account?->name),
                'asset_type' => $target->asset_type,
                'target_percentage' => (float) $target->target_percentage,
                'current_percentage' => $currentValue > 0 ? round(($currentBucketValue / $currentValue) * 100, 2) : 0.0,
                'current_value' => round($currentBucketValue, 2),
                'target_value' => round($desiredValue, 2),
                'delta_value' => round($desiredValue - $currentBucketValue, 2),
            ];
        });

        return [
            'current_value' => $summary['current_value'],
            'targets' => $targets->all(),
        ];
    }

    /**
     * @return Collection<int, Holding>
     */
    private function portfolioHoldings(Portfolio $portfolio): Collection
    {
        return Holding::query()
            ->with(['account', 'asset'])
            ->whereHas('account', fn ($query) => $query->where('portfolio_id', $portfolio->id))
            ->get();
    }

    /**
     * @param  Collection<int, Holding>  $holdings
     * @return Collection<int, PriceQuote>
     */
    private function latestQuotesForHoldings(Collection $holdings): Collection
    {
        $assetIds = $holdings->pluck('asset_id')->unique()->filter()->values();

        return PriceQuote::query()
            ->whereIn('asset_id', $assetIds)
            ->orderByDesc('quoted_at')
            ->get()
            ->unique('asset_id')
            ->keyBy('asset_id');
    }

    /**
     * @param  array<string, float>  $values
     * @return array<int, array<string, float|string>>
     */
    private function percentBreakdown(array $values, float $total): array
    {
        return collect($values)
            ->map(fn (float $value, string $label) => [
                'label' => $label,
                'value' => round($value, 2),
                'percentage' => $total > 0 ? round(($value / $total) * 100, 2) : 0.0,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $portfolioPoints
     * @return array<int, array<string, mixed>>
     */
    private function benchmarkSeries(Portfolio $portfolio, Collection $portfolioPoints): array
    {
        if ($portfolioPoints->isEmpty()) {
            return [];
        }

        [$benchmarkSymbol] = app(MarketHistoryService::class)->resolveBenchmark($portfolio);
        $startDate = $portfolioPoints->first()['date'];
        $series = BenchmarkPriceHistory::query()
            ->where('symbol', $benchmarkSymbol)
            ->whereDate('price_date', '>=', $startDate)
            ->orderBy('price_date')
            ->get();

        if ($series->isEmpty()) {
            return [];
        }

        $mapped = $series->map(fn ($point) => [
            'date' => $point->price_date->toDateString(),
            'value' => round((float) $point->close_price, 2),
        ]);

        return $this->normalizeSeries($this->alignSeries($portfolioPoints, $mapped));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $points
     * @return Collection<int, array<string, mixed>>
     */
    private function applyRange(Collection $points, string $range): Collection
    {
        if ($points->isEmpty()) {
            return collect();
        }

        $range = strtolower($range);

        return match ($range) {
            '1d' => $points->take(-2)->values(),
            '5d' => $points->take(-5)->values(),
            '3m' => $points->filter(fn (array $point) => Carbon::parse($point['date'])->gte(today()->subMonthsNoOverflow(3)))->values(),
            '1y' => $points->filter(fn (array $point) => Carbon::parse($point['date'])->gte(today()->subYear()))->values(),
            'ytd' => $points->filter(fn (array $point) => Carbon::parse($point['date'])->gte(today()->startOfYear()))->values(),
            default => $points->filter(fn (array $point) => Carbon::parse($point['date'])->gte(today()->subMonth()))->values(),
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $points
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSeries(Collection $points): array
    {
        if ($points->isEmpty()) {
            return [];
        }

        $first = max((float) $points->first()['value'], 0.01);

        return $points->map(fn (array $point) => [
            'date' => $point['date'],
            'value' => round((((float) $point['value']) / $first) * 100, 2),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $portfolioPoints
     * @param  Collection<int, array<string, mixed>>  $benchmarkPoints
     * @return Collection<int, array<string, mixed>>
     */
    private function alignSeries(Collection $portfolioPoints, Collection $benchmarkPoints): Collection
    {
        $benchmarkByDate = $benchmarkPoints->keyBy('date');

        return $portfolioPoints
            ->filter(fn (array $point) => $benchmarkByDate->has($point['date']))
            ->map(fn (array $point) => $benchmarkByDate->get($point['date']))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $points
     */
    private function rangeReturn(Collection $points): float
    {
        if ($points->count() < 2) {
            return 0.0;
        }

        $first = max((float) $points->first()['value'], 0.01);
        $last = (float) $points->last()['value'];

        return round((($last - $first) / $first) * 100, 2);
    }
}
