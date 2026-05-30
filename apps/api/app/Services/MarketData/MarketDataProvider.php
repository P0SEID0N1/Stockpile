<?php

namespace App\Services\MarketData;

interface MarketDataProvider
{
    /**
     * @param  array<int, string>  $symbols
     * @return array<string, array<string, mixed>>
     */
    public function fetchQuotes(array $symbols): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchDailyHistory(string $symbol, string $startDate, ?string $endDate = null): array;
}
