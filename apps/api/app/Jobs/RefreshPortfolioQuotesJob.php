<?php

namespace App\Jobs;

use App\Services\QuoteRefreshService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshPortfolioQuotesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ?int $portfolioId = null,
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(QuoteRefreshService $quoteRefreshService): void
    {
        $quoteRefreshService->refresh($this->portfolioId);
    }
}
