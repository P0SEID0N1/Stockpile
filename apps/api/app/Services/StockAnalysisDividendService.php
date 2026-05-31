<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetPriceHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class StockAnalysisDividendService
{
    /**
     * @return array{yield_percent: float, annual_dividend_per_share: float, history: array<int, array{date: string, dividend_cash: float}>}|null
     */
    public function fetchDividendSnapshot(Asset $asset): ?array
    {
        $html = $this->downloadDividendPage($asset);

        if ($html === null) {
            return null;
        }

        return $this->extractDividendSnapshot($html);
    }

    public function syncDividendSnapshot(Asset $asset, ?Carbon $cutoffDate = null): void
    {
        $metadata = is_array($asset->metadata) ? $asset->metadata : [];
        $lastSyncedAt = isset($metadata['dividend_last_synced_at'])
            ? Carbon::parse((string) $metadata['dividend_last_synced_at'])
            : null;

        if ($lastSyncedAt && $lastSyncedAt->gte(now()->subHours(12))) {
            return;
        }

        $snapshot = $this->fetchDividendSnapshot($asset);

        if (! $snapshot) {
            return;
        }

        $metadata['dividend_yield_percent'] = $snapshot['yield_percent'];
        $metadata['annual_dividend_per_share'] = $snapshot['annual_dividend_per_share'];
        $metadata['dividend_source'] = 'stockanalysis';
        $metadata['dividend_last_synced_at'] = now()->toIso8601String();

        $asset->forceFill(['metadata' => $metadata])->save();

        $historyRows = collect($snapshot['history'])
            ->filter(function (array $row) use ($cutoffDate) {
                return $cutoffDate === null || Carbon::parse($row['date'])->gte($cutoffDate);
            });

        if ($historyRows->isEmpty()) {
            return;
        }

        $existingRows = AssetPriceHistory::query()
            ->where('asset_id', $asset->id)
            ->whereIn('price_date', $historyRows->pluck('date')->all())
            ->get()
            ->keyBy(fn (AssetPriceHistory $row) => $row->price_date->toDateString());

        foreach ($historyRows as $row) {
            $existing = $existingRows->get($row['date']);

            if (! $existing) {
                continue;
            }

            $existing->forceFill([
                'dividend_cash' => $row['dividend_cash'],
                'source' => $existing->source === 'tiingo' ? 'tiingo+stockanalysis' : $existing->source,
            ])->save();
        }
    }

    /**
     * @return array{yield_percent: float, annual_dividend_per_share: float, history: array<int, array{date: string, dividend_cash: float}>}|null
     */
    public function extractDividendSnapshot(string $html): ?array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? strip_tags($html));
        $normalized = $text;

        if (! preg_match('/dividend yield of ([0-9]+(?:\.[0-9]+)?)% and paid \$([0-9]+(?:\.[0-9]+)?) per share in the past year/i', $normalized, $summaryMatch)) {
            return null;
        }

        preg_match_all('/([A-Z][a-z]{2} \d{1,2}, \d{4})\s*\$([0-9]+(?:\.[0-9]+)?)/', $text, $historyMatches, PREG_SET_ORDER);

        $history = collect($historyMatches)
            ->map(function (array $match) {
                return [
                    'date' => Carbon::parse($match[1])->toDateString(),
                    'dividend_cash' => round((float) $match[2], 6),
                ];
            })
            ->unique('date')
            ->values()
            ->all();

        return [
            'yield_percent' => round((float) $summaryMatch[1], 2),
            'annual_dividend_per_share' => round((float) $summaryMatch[2], 6),
            'history' => $history,
        ];
    }

    private function downloadDividendPage(Asset $asset): ?string
    {
        foreach ($this->candidateUrls($asset) as $url) {
            try {
                $response = Http::accept('text/html')
                    ->timeout(10)
                    ->get($url);
            } catch (Throwable) {
                continue;
            }

            if ($response->successful() && is_string($response->body()) && str_contains($response->body(), 'Dividend')) {
                return $response->body();
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function candidateUrls(Asset $asset): array
    {
        $symbol = strtolower((string) $asset->symbol);
        $type = strtolower((string) $asset->asset_type);

        return match (true) {
            str_contains($type, 'etf') => [
                "https://stockanalysis.com/etf/{$symbol}/dividend/",
                "https://stockanalysis.com/stocks/{$symbol}/dividend/",
            ],
            str_contains($type, 'stock') => [
                "https://stockanalysis.com/stocks/{$symbol}/dividend/",
                "https://stockanalysis.com/etf/{$symbol}/dividend/",
            ],
            default => [
                "https://stockanalysis.com/stocks/{$symbol}/dividend/",
                "https://stockanalysis.com/etf/{$symbol}/dividend/",
            ],
        };
    }
}
