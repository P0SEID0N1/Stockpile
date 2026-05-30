<?php

namespace Tests\Feature\Unit;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\HoldingSnapshot;
use App\Models\Portfolio;
use App\Models\PriceQuote;
use App\Models\User;
use App\Services\PortfolioAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_and_timeseries_reflect_holdings_and_quotes(): void
    {
        $user = User::query()->create([
            'name' => 'Max',
            'email' => 'max@example.com',
            'password' => 'password123',
        ]);
        $portfolio = Portfolio::query()->create([
            'user_id' => $user->id,
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'SPY',
            'benchmark_name' => 'S&P 500 ETF',
        ]);
        $account = Account::query()->create([
            'portfolio_id' => $portfolio->id,
            'name' => 'Brokerage',
            'type' => 'taxable',
            'currency' => 'USD',
        ]);
        $asset = Asset::query()->create([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc.',
            'asset_type' => 'stocks',
            'currency' => 'USD',
        ]);
        $holding = Holding::query()->create([
            'account_id' => $account->id,
            'asset_id' => $asset->id,
            'quantity' => 10,
            'cost_basis_total' => 1500,
            'market_value' => 1500,
        ]);
        HoldingSnapshot::query()->create([
            'holding_id' => $holding->id,
            'snapshot_date' => '2026-05-28',
            'quantity' => 10,
            'cost_basis_total' => 1500,
            'market_value' => 1800,
            'price_per_unit' => 180,
            'source_type' => 'csv',
        ]);
        PriceQuote::query()->create([
            'asset_id' => $asset->id,
            'price' => 190,
            'currency' => 'USD',
            'price_date' => '2026-05-29',
            'quoted_at' => now(),
            'source' => 'demo',
            'day_change' => 2,
            'day_change_percent' => 1.06,
        ]);

        $service = app(PortfolioAnalyticsService::class);
        $summary = $service->summary($portfolio);
        $series = $service->timeSeries($portfolio);

        $this->assertSame(1900.0, $summary['current_value']);
        $this->assertSame(400.0, $summary['total_gain_loss']);
        $this->assertCount(2, $series['portfolio']);
    }
}
