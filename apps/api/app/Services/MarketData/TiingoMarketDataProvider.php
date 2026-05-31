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

    public function fetchDailyHistory(string $symbol, string $startDate, ?string $endDate = null): array
    {
        if ($this->apiToken === '') {
            return [];
        }

        $response = $this->request()->get("/tiingo/daily/{$symbol}/prices", [
            'startDate' => $startDate,
            'endDate' => $endDate ?? today()->toDateString(),
            'resampleFreq' => 'daily',
        ]);

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        return collect($payload)
            ->filter(fn ($row) => is_array($row) && isset($row['date']) && isset($row['close']))
            ->map(function (array $row) use ($symbol) {
                $date = Carbon::parse((string) $row['date']);

                return [
                    'symbol' => strtoupper((string) ($row['ticker'] ?? $symbol)),
                    'date' => $date->toDateString(),
                    'open_price' => isset($row['open']) && is_numeric($row['open']) ? round((float) $row['open'], 6) : null,
                    'high_price' => isset($row['high']) && is_numeric($row['high']) ? round((float) $row['high'], 6) : null,
                    'low_price' => isset($row['low']) && is_numeric($row['low']) ? round((float) $row['low'], 6) : null,
                    'close_price' => round((float) $row['close'], 6),
                    'adj_close_price' => isset($row['adjClose']) && is_numeric($row['adjClose']) ? round((float) $row['adjClose'], 6) : null,
                    'dividend_cash' => isset($row['divCash']) && is_numeric($row['divCash']) ? round((float) $row['divCash'], 6) : 0.0,
                    'split_factor' => isset($row['splitFactor']) && is_numeric($row['splitFactor']) ? round((float) $row['splitFactor'], 6) : 1.0,
                    'source' => 'tiingo',
                ];
            })
            ->values()
            ->all();
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

        $priceDate = isset($payload['date']) ? Carbon::parse((string) $payload['date']) : now();
        $quotedAt = now();
        $previousClose = $payload['prevClose'] ?? $payload['previousClose'] ?? null;
        $dayChange = is_numeric($previousClose) ? round((float) $price - (float) $previousClose, 6) : null;
        $dayChangePercent = is_numeric($previousClose) && (float) $previousClose !== 0.0
            ? round(($dayChange / (float) $previousClose) * 100, 4)
            : null;

        return [
            'symbol' => $symbol,
            'price' => round((float) $price, 6),
            'quoted_at' => $quotedAt,
            'price_date' => $priceDate->toDateString(),
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
