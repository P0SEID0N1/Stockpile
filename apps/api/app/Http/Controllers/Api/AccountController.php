<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    use InteractsWithPortfolio;

    public function index(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);

        return response()->json(
            $portfolio->accounts()->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'institution' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $account = $portfolio->accounts()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'institution' => $validated['institution'] ?? null,
            'currency' => strtoupper($validated['currency'] ?? $portfolio->base_currency),
        ]);

        return response()->json($account, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $account = $portfolio->accounts()->whereKey($id)->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:100'],
            'institution' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['currency'])) {
            $validated['currency'] = strtoupper($validated['currency']);
        }

        $account->fill($validated)->save();

        return response()->json($account->refresh());
    }
}
