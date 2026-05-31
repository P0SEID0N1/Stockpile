<?php

namespace Tests\Feature\Unit;

use App\Models\ApiToken;
use App\Models\Holding;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\MarketData\MarketDataProvider;
use App\Services\PortfolioLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.market_data.provider' => 'demo']);
        app()->forgetInstance(MarketDataProvider::class);
    }

    public function test_backdated_buy_generates_dividend_and_reinvestment_entries(): void
    {
        $user = User::query()->create([
            'name' => 'Max',
            'email' => 'max@example.com',
            'password' => 'password123',
        ]);
        $portfolio = $user->portfolios()->create([
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'W5000',
            'benchmark_name' => 'Wilshire 5000',
        ]);

        app(PortfolioLedgerService::class)->recordBuy($portfolio, [
            'symbol' => 'VTI',
            'trade_date' => '2026-05-10',
            'purchase_price' => 100,
            'quantity' => 10,
            'total_cost' => 1000,
            'origin' => 'test',
        ]);

        $this->assertTrue(
            JournalEntry::query()
                ->where('portfolio_id', $portfolio->id)
                ->where('entry_type', 'dividend')
                ->where('source_type', 'auto_dividend')
                ->whereDate('trade_date', '2026-05-15')
                ->exists()
        );
        $this->assertTrue(
            JournalEntry::query()
                ->where('portfolio_id', $portfolio->id)
                ->where('entry_type', 'dividend_reinvested')
                ->where('source_type', 'auto_dividend')
                ->whereDate('trade_date', '2026-05-15')
                ->exists()
        );

        $holding = Holding::query()->firstOrFail()->load('journalEntries');
        $this->assertSame(1000.0, $holding->manualNetInvestedTotal());
        $this->assertGreaterThan(1000.0, (float) $holding->cost_basis_total);
        $this->assertGreaterThan(0.0, $holding->dripBasisAdjustment());
        $this->assertNotNull($holding->load('asset.priceHistory')->trailingDividendYieldPercent());
    }

    public function test_timeseries_supports_range_metadata(): void
    {
        [$user, $token] = $this->issueToken();
        $portfolio = $user->portfolios()->create([
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'W5000',
            'benchmark_name' => 'Wilshire 5000',
        ]);

        app(PortfolioLedgerService::class)->recordBuy($portfolio, [
            'symbol' => 'AAPL',
            'trade_date' => '2026-05-01',
            'purchase_price' => 200,
            'quantity' => 5,
            'total_cost' => 1000,
            'origin' => 'test',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/performance/timeseries?portfolio_id='.$portfolio->id.'&range=1d')
            ->assertOk()
            ->assertJsonPath('range', '1d')
            ->assertJsonStructure([
                'range',
                'portfolio',
                'comparison_portfolio',
                'benchmark',
                'portfolio_return_percent',
                'benchmark_return_percent',
                'benchmark_symbol',
                'benchmark_label',
            ]);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function issueToken(): array
    {
        $user = User::query()->create([
            'name' => 'Max',
            'email' => 'token@example.com',
            'password' => 'password123',
        ]);
        [, $plainTextToken] = ApiToken::issueFor($user, 'test-suite');

        return [$user, $plainTextToken];
    }
}
