<?php

namespace App\Console\Commands;

use App\Services\QuoteRefreshService;
use Illuminate\Console\Command;

class RefreshQuotesCommand extends Command
{
    protected $signature = 'portfolio:refresh-quotes {--portfolio=}';

    protected $description = 'Refresh portfolio quotes and benchmark series';

    public function handle(QuoteRefreshService $quoteRefreshService): int
    {
        $result = $quoteRefreshService->refresh(
            portfolioId: $this->option('portfolio') ? (int) $this->option('portfolio') : null,
        );

        $this->info(sprintf(
            'Refreshed %d quotes and %d benchmark points using %s.',
            $result['quotes_refreshed'],
            $result['benchmarks_refreshed'],
            $result['provider'],
        ));

        return self::SUCCESS;
    }
}
