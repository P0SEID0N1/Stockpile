<?php

namespace Tests\Feature\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioApiTest extends TestCase
{
    use RefreshDatabase;

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
