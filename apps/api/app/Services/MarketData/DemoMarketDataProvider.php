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
}
