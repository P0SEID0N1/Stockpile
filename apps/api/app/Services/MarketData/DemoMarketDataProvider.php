<?php

namespace App\Services\MarketData;

class DemoMarketDataProvider implements MarketDataProvider
{
    public function fetchQuotes(array $symbols): array
    {
        $quotes = [];

        foreach ($symbols as $symbol) {
            $seed = abs(crc32($symbol));
            $base = 25 + ($seed % 250);
            $drift = ((int) now()->format('z') % 10) * 0.75;
            $price = round($base + $drift, 2);
            $dayChange = round((($seed % 11) - 5) * 0.37, 2);
            $prior = max($price - $dayChange, 0.01);

            $quotes[$symbol] = [
                'symbol' => $symbol,
                'price' => $price,
                'quoted_at' => now(),
                'price_date' => today(),
                'currency' => 'USD',
                'source' => 'demo',
                'day_change' => $dayChange,
                'day_change_percent' => round(($dayChange / $prior) * 100, 4),
            ];
        }

        return $quotes;
    }

    public function fetchDailyHistory(string $symbol, string $startDate, ?string $endDate = null): array
    {
        $rows = [];
        $cursor = now()->parse($startDate)->startOfDay();
        $finalDate = now()->parse($endDate ?? today()->toDateString())->startOfDay();
        $seed = abs(crc32($symbol));
        $base = 25 + ($seed % 250);
        $day = 0;

        while ($cursor->lte($finalDate)) {
            if (! $cursor->isWeekend()) {
                $price = round($base + sin($day / 3) * 4 + $day * 0.15, 2);
                $dividend = $cursor->day === 15 ? round((($seed % 7) + 1) * 0.05, 4) : 0.0;

                $rows[] = [
                    'symbol' => strtoupper($symbol),
                    'date' => $cursor->toDateString(),
                    'open_price' => $price - 0.35,
                    'high_price' => $price + 0.55,
                    'low_price' => max($price - 0.75, 0.01),
                    'close_price' => $price,
                    'adj_close_price' => $price,
                    'dividend_cash' => $dividend,
                    'split_factor' => 1.0,
                    'source' => 'demo',
                ];
            }

            $cursor->addDay();
            $day++;
        }

        return $rows;
    }
}
