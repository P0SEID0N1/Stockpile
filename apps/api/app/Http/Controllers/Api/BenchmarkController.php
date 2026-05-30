<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Models\BenchmarkSeries;
use App\Services\QuoteRefreshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BenchmarkController extends Controller
{
    use InteractsWithPortfolio;

    public function index(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        return response()->json([
            'symbol' => $portfolio->benchmark_symbol,
            'name' => $portfolio->benchmark_name,
            'series' => BenchmarkSeries::query()
                ->where('symbol', $portfolio->benchmark_symbol)
                ->latest('series_date')
                ->limit(30)
                ->get()
                ->reverse()
                ->values(),
        ]);
    }

    public function select(Request $request, QuoteRefreshService $quoteRefreshService): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'benchmark_symbol' => ['required', 'string', 'max:20'],
            'benchmark_name' => ['nullable', 'string', 'max:255'],
        ]);

        $portfolio->forceFill([
            'benchmark_symbol' => strtoupper($validated['benchmark_symbol']),
            'benchmark_name' => $validated['benchmark_name'] ?? strtoupper($validated['benchmark_symbol']),
        ])->save();

        $quoteRefreshService->refresh($portfolio->id);

        return response()->json([
            'message' => 'Benchmark updated.',
            'portfolio' => $portfolio->refresh(),
        ]);
    }
}
