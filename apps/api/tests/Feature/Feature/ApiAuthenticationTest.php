<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_logout_via_api_token(): void
    {
        $user = User::query()->create([
            'name' => 'Max',
            'email' => 'max@example.com',
            'password' => 'password123',
        ]);
        $user->portfolios()->create([
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'SPY',
            'benchmark_name' => 'S&P 500 ETF',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'max@example.com',
            'password' => 'password123',
            'device_name' => 'test-suite',
        ]);

        $login
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'email'],
                'portfolios',
            ]);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'max@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }
}
