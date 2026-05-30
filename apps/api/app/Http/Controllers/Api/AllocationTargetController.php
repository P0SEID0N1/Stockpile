<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Services\PortfolioAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllocationTargetController extends Controller
{
    use InteractsWithPortfolio;

    public function __construct(
        private readonly PortfolioAnalyticsService $portfolioAnalyticsService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        return response()->json(
            $this->portfolioAnalyticsService->planner($portfolio)
        );
    }

    public function update(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.label' => ['nullable', 'string', 'max:255'],
            'targets.*.asset_type' => ['nullable', 'string', 'max:50'],
            'targets.*.target_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $portfolio->allocationTargets()->delete();

        foreach ($validated['targets'] as $target) {
            $portfolio->allocationTargets()->create($target);
        }

        return response()->json(
            $this->portfolioAnalyticsService->planner($portfolio->refresh())
        );
    }
}
