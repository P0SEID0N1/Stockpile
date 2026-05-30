<?php

namespace Tests\Feature\Feature;

use App\Models\ApiToken;
use App\Models\Holding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HoldingsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_holdings_csv_can_be_previewed_and_committed(): void
    {
        $user = User::query()->create([
            'name' => 'Max',
            'email' => 'max@example.com',
            'password' => 'password123',
        ]);
        $portfolio = $user->portfolios()->create([
            'name' => 'Main Portfolio',
            'base_currency' => 'USD',
            'benchmark_symbol' => 'SPY',
            'benchmark_name' => 'S&P 500 ETF',
        ]);
        [, $plainTextToken] = ApiToken::issueFor($user, 'test-suite');

        $file = UploadedFile::fake()->createWithContent(
            'holdings.csv',
            implode("\n", [
                'account_name,account_type,symbol,asset_type,quantity,cost_basis_total,snapshot_date',
                'Brokerage,taxable,AAPL,stocks,10,1750.00,2026-05-29',
            ]),
        );

        $preview = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->post('/api/imports/holdings-csv?portfolio_id='.$portfolio->id, [
                'file' => $file,
            ]);

        $preview
            ->assertCreated()
            ->assertJsonPath('status', 'preview');

        $importJobId = $preview->json('id');

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->postJson('/api/imports/'.$importJobId.'/commit')
            ->assertOk()
            ->assertJsonPath('status', 'committed');

        $this->assertDatabaseCount('holdings', 1);
        $this->assertSame('AAPL', Holding::query()->with('asset')->firstOrFail()->asset->symbol);
    }
}
