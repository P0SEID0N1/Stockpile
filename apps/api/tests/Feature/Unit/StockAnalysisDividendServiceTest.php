<?php

namespace Tests\Feature\Unit;

use App\Services\StockAnalysisDividendService;
use Tests\TestCase;

class StockAnalysisDividendServiceTest extends TestCase
{
    public function test_it_extracts_dividend_yield_and_history_from_stockanalysis_markup(): void
    {
        $html = <<<'HTML'
        <html>
            <body>
                <h1>XSHD Dividend Information</h1>
                <p>XSHD has a dividend yield of 5.32% and paid $0.72 per share in the past year. The dividend is paid every month and the last ex-dividend date was May 18, 2026.</p>
                <table>
                    <tr><td>May 18, 2026</td><td>$0.05605</td></tr>
                    <tr><td>Apr 20, 2026</td><td>$0.05798</td></tr>
                    <tr><td>Mar 23, 2026</td><td>$0.05346</td></tr>
                </table>
            </body>
        </html>
        HTML;

        $snapshot = app(StockAnalysisDividendService::class)->extractDividendSnapshot($html);

        $this->assertNotNull($snapshot);
        $this->assertSame(5.32, $snapshot['yield_percent']);
        $this->assertSame(0.72, $snapshot['annual_dividend_per_share']);
        $this->assertCount(3, $snapshot['history']);
        $this->assertSame('2026-05-18', $snapshot['history'][0]['date']);
        $this->assertSame(0.05605, $snapshot['history'][0]['dividend_cash']);
    }
}
