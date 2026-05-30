<?php

namespace App\Services\MarketData;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class TiingoMarketDataProvider implements MarketDataProvider
{
    public function __construct(
        private readonly string $apiToken,
        private readonly string $baseUrl,
    ) {
    }

    public function fetchQuotes(array $symbols): array
    {
        $symbols = collect($symbols)
            ->filter(fn ($symbol) => is_string($symbol) && trim($symbol) !== '')
            ->map(fn ($symbol) => strtoupper(trim($symbol)))
            ->unique()
            ->values()
            ->all();

        if ($this->apiToken === '' || $symbols === []) {
            return [];
        }

        $quotes = $this->fetchBulkQuotes($symbols);
        $missingSymbols = array_values(array_diff($symbols, array_keys($quotes)));

        foreach ($missingSymbols as $symbol) {
            $quote = $this->fetchSingleQuote($symbol);

            if ($quote !== null) {
                $quotes[$symbol] = $quote;
            }
        }

        return $quotes;
    }

    /**
     * @param  array<int, string>  $symbols
     * @return array<string, array<string, mixed>>
     */
    private function fetchBulkQuotes(array $symbols): array
    {
        $response = $this->request()
            ->get('/tiingo/daily/prices', [
                'tickers' => implode(',', $symbols),
            ]);

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        $quotes = [];

        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $quote = $this->normalizeQuote($row);

            if ($quote !== null) {
                $quotes[$quote['symbol']] = $quote;
            }
        }

        return $quotes;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchSingleQuote(string $symbol): ?array
    {
        $response = $this->request()
            ->get("/tiingo/daily/{$symbol}/prices", [
                'startDate' => now()->subDays(7)->toDateString(),
                'endDate' => today()->toDateString(),
                'resampleFreq' => 'daily',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || $payload === []) {
            return null;
        }

        $row = collect($payload)
            ->filter(fn ($candidate) => is_array($candidate))
            ->sortByDesc(fn (array $candidate) => (string) ($candidate['date'] ?? ''))
            ->first();

        if (! is_array($row)) {
            return null;
        }

        $row['ticker'] = $row['ticker'] ?? $symbol;

        return $this->normalizeQuote($row);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function normalizeQuote(array $payload): ?array
    {
        $symbol = strtoupper((string) ($payload['ticker'] ?? $payload['symbol'] ?? ''));
        $price = $payload['close'] ?? $payload['adjClose'] ?? null;

        if ($symbol === '' || ! is_numeric($price)) {
            return null;
        }

        $quotedAt = isset($payload['date']) ? Carbon::parse((string) $payload['date']) : now();
        $previousClose = $payload['prevClose'] ?? $payload['previousClose'] ?? null;
        $dayChange = is_numeric($previousClose) ? round((float) $price - (float) $previousClose, 6) : null;
        $dayChangePercent = is_numeric($previousClose) && (float) $previousClose !== 0.0
            ? round(($dayChange / (float) $previousClose) * 100, 4)
            : null;

        return [
            'symbol' => $symbol,
            'price' => round((float) $price, 6),
            'quoted_at' => $quotedAt,
            'price_date' => $quotedAt->toDateString(),
            'currency' => 'USD',
            'source' => 'tiingo',
            'day_change' => $dayChange,
            'day_change_percent' => $dayChangePercent,
        ];
    }

    private function request()
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Token '.$this->apiToken,
            ])
            ->timeout(10)
            ->withQueryParameters([
                'token' => $this->apiToken,
            ]);
    }
}
