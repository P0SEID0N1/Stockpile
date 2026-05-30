<?php

namespace App\Services\MarketData;

use Illuminate\Support\Facades\Http;

class AlphaVantageMarketDataProvider implements MarketDataProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {
    }

    public function fetchQuotes(array $symbols): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        $quotes = [];

        foreach ($symbols as $symbol) {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout(10)
                ->get('/query', [
                    'function' => 'GLOBAL_QUOTE',
                    'symbol' => $symbol,
                    'apikey' => $this->apiKey,
                ]);

            $payload = $response->json('Global Quote', []);

            if (! is_array($payload) || ! isset($payload['05. price'])) {
                continue;
            }

            $price = (float) $payload['05. price'];
            $dayChange = (float) ($payload['09. change'] ?? 0);
            $quotes[$symbol] = [
                'symbol' => $symbol,
                'price' => $price,
                'quoted_at' => now(),
                'price_date' => today(),
                'currency' => 'USD',
                'source' => 'alphavantage',
                'day_change' => $dayChange,
                'day_change_percent' => (float) str_replace('%', '', (string) ($payload['10. change percent'] ?? 0)),
            ];
        }

        return $quotes;
    }
}
