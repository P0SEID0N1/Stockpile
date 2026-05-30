<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Holding;
use App\Services\PortfolioAnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use InteractsWithPortfolio;

    public function __construct(
        private readonly PortfolioAnalyticsService $portfolioAnalyticsService,
    ) {
    }

    public function index(Request $request)
    {
        $portfolio = $this->resolvePortfolio($request);

        return view('dashboard.index', [
            'portfolio' => $portfolio,
            'summary' => $this->portfolioAnalyticsService->summary($portfolio),
            'series' => $this->portfolioAnalyticsService->timeSeries($portfolio),
            'recentHoldings' => Holding::query()
                ->with(['account', 'asset'])
                ->whereHas('account', fn ($query) => $query->where('portfolio_id', $portfolio->id))
                ->orderByDesc('market_value')
                ->limit(10)
                ->get(),
        ]);
    }

    public function portfolio(Request $request)
    {
        $portfolio = $this->resolvePortfolio($request);

        return view('portfolio.index', [
            'portfolio' => $portfolio,
            'accounts' => $portfolio->accounts()->with('holdings.asset')->get(),
        ]);
    }

    public function performance(Request $request)
    {
        $portfolio = $this->resolvePortfolio($request);

        return view('performance.index', [
            'portfolio' => $portfolio,
            'summary' => $this->portfolioAnalyticsService->summary($portfolio),
            'series' => $this->portfolioAnalyticsService->timeSeries($portfolio),
        ]);
    }

    public function journal(Request $request)
    {
        $portfolio = $this->resolvePortfolio($request);

        return view('journal.index', [
            'portfolio' => $portfolio,
            'entries' => $portfolio->journalEntries()->with(['account', 'holding.asset'])->latest('trade_date')->get(),
        ]);
    }

    public function plan(Request $request)
    {
        $portfolio = $this->resolvePortfolio($request);

        return view('plan.index', [
            'portfolio' => $portfolio,
            'planner' => $this->portfolioAnalyticsService->planner($portfolio),
        ]);
    }

    public function settings(Request $request)
    {
        $portfolio = $this->resolvePortfolio($request);

        return view('settings.index', [
            'portfolio' => $portfolio,
            'user' => $request->user(),
        ]);
    }
}
