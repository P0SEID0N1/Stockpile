<?php

namespace Tests\Feature\Unit;

use App\Services\MarketData\TiingoMarketDataProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TiingoMarketDataProviderTest extends TestCase
{
    public function test_it_maps_bulk_daily_prices_to_quotes(): void
    {
        Http::fake([
            'https://api.tiingo.com/tiingo/daily/prices*' => Http::response([
                [
                    'ticker' => 'AAPL',
                    'date' => '2026-05-29T00:00:00.000Z',
                    'close' => 201.45,
                    'prevClose' => 198.10,
                ],
            ]),
        ]);

        $provider = new TiingoMarketDataProvider(
            apiToken: 'test-token',
            baseUrl: 'https://api.tiingo.com',
        );

        $quotes = $provider->fetchQuotes(['AAPL']);

        $this->assertArrayHasKey('AAPL', $quotes);
        $this->assertSame('tiingo', $quotes['AAPL']['source']);
        $this->assertSame(201.45, $quotes['AAPL']['price']);
        $this->assertSame('2026-05-29', $quotes['AAPL']['price_date']);
        $this->assertSame(3.35, $quotes['AAPL']['day_change']);
        $this->assertSame(1.6911, $quotes['AAPL']['day_change_percent']);
    }

    public function test_it_falls_back_to_single_symbol_prices_when_bulk_response_misses_a_symbol(): void
    {
        Http::fake([
            'https://api.tiingo.com/tiingo/daily/prices*' => Http::response([]),
            'https://api.tiingo.com/tiingo/daily/MSFT/prices*' => Http::response([
                [
                    'ticker' => 'MSFT',
                    'date' => '2026-05-29T00:00:00.000Z',
                    'close' => 430.11,
                ],
            ]),
        ]);

        $provider = new TiingoMarketDataProvider(
            apiToken: 'test-token',
            baseUrl: 'https://api.tiingo.com',
        );

        $quotes = $provider->fetchQuotes(['MSFT']);

        $this->assertArrayHasKey('MSFT', $quotes);
        $this->assertSame(430.11, $quotes['MSFT']['price']);
        $this->assertNull($quotes['MSFT']['day_change']);
        $this->assertNull($quotes['MSFT']['day_change_percent']);
    }
}
