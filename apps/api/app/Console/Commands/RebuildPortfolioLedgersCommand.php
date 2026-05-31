<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
use App\Services\PortfolioLedgerService;
use Illuminate\Console\Command;

class RebuildPortfolioLedgersCommand extends Command
{
    protected $signature = 'portfolio:rebuild-ledgers {--portfolio=}';

    protected $description = 'Rebuild holdings and historical snapshots for one portfolio or all portfolios.';

    public function handle(PortfolioLedgerService $portfolioLedgerService): int
    {
        $portfolios = Portfolio::query()
            ->when(
                $this->option('portfolio'),
                fn ($query, $portfolioId) => $query->whereKey((int) $portfolioId)
            )
            ->get();

        if ($portfolios->isEmpty()) {
            $this->warn('No portfolios matched the selection.');

            return self::SUCCESS;
        }

        foreach ($portfolios as $portfolio) {
            $portfolioLedgerService->rebuildPortfolio($portfolio);
            $this->line("Rebuilt portfolio {$portfolio->id} ({$portfolio->name}).");
        }

        $this->info("Rebuilt {$portfolios->count()} portfolio ledger(s).");

        return self::SUCCESS;
    }
}
