<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
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
}
