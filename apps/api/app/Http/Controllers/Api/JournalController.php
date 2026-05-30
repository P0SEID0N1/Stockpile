<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Services\PortfolioLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    use InteractsWithPortfolio;

    public function index(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        return response()->json(
            $portfolio->journalEntries()
                ->with(['account', 'asset', 'holding.asset', 'linkedEntry', 'reinvestmentEntry'])
                ->orderByDesc('trade_date')
                ->orderByDesc('id')
                ->get()
        );
    }

    public function store(Request $request, PortfolioLedgerService $portfolioLedgerService): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'asset_id' => ['nullable', 'integer'],
            'symbol' => ['required_without:asset_id', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'asset_type' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'entry_type' => ['required', 'in:buy,sell,dividend,dividend_reinvested'],
            'trade_date' => ['required', 'date'],
            'quantity' => ['nullable', 'numeric'],
            'price_per_unit' => ['nullable', 'numeric'],
            'amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $journalEntry = $portfolioLedgerService->recordManualTransaction($portfolio, $validated);

        return response()->json($journalEntry, 201);
    }
}
