<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Services\PortfolioLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    use InteractsWithPortfolio;

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->portfolios()->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_currency' => ['nullable', 'string', 'size:3'],
            'benchmark_symbol' => ['nullable', 'string', 'max:20'],
            'benchmark_name' => ['nullable', 'string', 'max:255'],
        ]);

        $portfolio = $request->user()->portfolios()->create([
            'name' => $validated['name'],
            'base_currency' => strtoupper($validated['base_currency'] ?? 'USD'),
            'benchmark_symbol' => strtoupper($validated['benchmark_symbol'] ?? 'SPY'),
            'benchmark_name' => $validated['benchmark_name'] ?? 'S&P 500 ETF',
        ]);

        return response()->json($portfolio, 201);
    }

    public function reset(Request $request, string $id, PortfolioLedgerService $portfolioLedgerService): JsonResponse
    {
        $portfolio = $request->user()->portfolios()->whereKey($id)->firstOrFail();
        $replacement = $portfolioLedgerService->resetPortfolio($portfolio);

        return response()->json([
            'message' => 'Portfolio reset successfully.',
            'portfolio' => $replacement,
        ]);
    }
}
