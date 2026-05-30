<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    use InteractsWithPortfolio;

    public function index(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        return response()->json(
            $portfolio->journalEntries()->with(['account', 'holding.asset'])->latest('trade_date')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'holding_id' => ['nullable', 'integer'],
            'entry_type' => ['required', 'string', 'max:50'],
            'trade_date' => ['required', 'date'],
            'quantity' => ['nullable', 'numeric'],
            'price_per_unit' => ['nullable', 'numeric'],
            'amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $journalEntry = $portfolio->journalEntries()->create($validated);

        return response()->json($journalEntry->load(['account', 'holding.asset']), 201);
    }
}
