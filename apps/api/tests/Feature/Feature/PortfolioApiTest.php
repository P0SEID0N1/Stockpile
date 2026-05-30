<?php

namespace Tests\Feature\Feature;

use App\Services\MarketData\MarketDataProvider;
use App\Models\ApiToken;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.market_data.provider' => 'demo']);
        app()->forgetInstance(MarketDataProvider::class);
    }

    public function test_authenticated_user_can_list_and_create_portfolios(): void
    {
        [$user, $token] = $this->issueToken();

        $user->portfolios()->create([
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'SPY',
            'benchmark_name' => 'S&P 500 ETF',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/portfolios')
            ->assertOk()
            ->assertJsonCount(1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/portfolios', [
                'name' => 'Retirement Portfolio',
                'base_currency' => 'USD',
                'benchmark_symbol' => 'QQQ',
                'benchmark_name' => 'Nasdaq 100',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Retirement Portfolio');
    }

    public function test_authenticated_user_can_add_holding_with_symbol_price_and_quantity(): void
    {
        [$user, $token] = $this->issueToken();
        $portfolio = $user->portfolios()->create([
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'SPY',
            'benchmark_name' => 'S&P 500 ETF',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/holdings', [
                'portfolio_id' => $portfolio->id,
                'symbol' => 'MSFT',
                'trade_date' => '2026-05-10',
                'purchase_price' => 420.50,
                'quantity' => 3,
                'total_cost' => 1261.50,
            ])
            ->assertCreated()
            ->assertJsonPath('asset.symbol', 'MSFT');

        $this->assertDatabaseHas('accounts', [
            'portfolio_id' => $portfolio->id,
            'name' => 'Primary Brokerage',
        ]);

        $asset = Asset::query()->where('symbol', 'MSFT')->firstOrFail();
        $account = Account::query()->where('portfolio_id', $portfolio->id)->firstOrFail();

        $this->assertDatabaseHas('holdings', [
            'account_id' => $account->id,
            'asset_id' => $asset->id,
        ]);

        $holding = Holding::query()->where('account_id', $account->id)->where('asset_id', $asset->id)->firstOrFail();
        $this->assertGreaterThan(1261.50, (float) $holding->cost_basis_total);
    }

    public function test_authenticated_user_can_reset_portfolio(): void
    {
        [$user, $token] = $this->issueToken();
        $portfolio = $user->portfolios()->create([
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'W5000',
            'benchmark_name' => 'Wilshire 5000',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/portfolios/'.$portfolio->id.'/reset')
            ->assertOk()
            ->assertJsonPath('portfolio.name', 'Main Portfolio');

        $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
        $this->assertDatabaseHas('portfolios', [
            'user_id' => $user->id,
            'name' => 'Main Portfolio',
            'benchmark_symbol' => 'W5000',
        ]);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function issueToken(): array
    {
        $user = User::query()->create([
            'name' => 'Max',
            'email' => 'max@example.com',
            'password' => 'password123',
        ]);
        [, $plainTextToken] = ApiToken::issueFor($user, 'test-suite');

        return [$user, $plainTextToken];
    }
}
