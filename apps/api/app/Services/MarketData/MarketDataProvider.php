<?php

namespace App\Services\MarketData;

interface MarketDataProvider
{
    /**
     * @param  array<int, string>  $symbols
     * @return array<string, array<string, mixed>>
     */
    public function fetchQuotes(array $symbols): array;
}
