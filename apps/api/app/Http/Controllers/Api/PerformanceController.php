<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Services\PortfolioAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    use InteractsWithPortfolio;

    public function __construct(
        private readonly PortfolioAnalyticsService $portfolioAnalyticsService,
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        return response()->json(
            $this->portfolioAnalyticsService->summary($portfolio)
        );
    }

    public function timeseries(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        return response()->json(
            $this->portfolioAnalyticsService->timeSeries(
                $portfolio,
                (string) $request->query('range', '1m'),
            )
        );
    }
}
