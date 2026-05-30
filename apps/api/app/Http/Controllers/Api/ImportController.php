<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Services\HoldingsImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    use InteractsWithPortfolio;

    public function __construct(
        private readonly HoldingsImportService $holdingsImportService,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'commit' => ['nullable', 'boolean'],
        ]);

        $importJob = $this->holdingsImportService->preview($request->user(), $portfolio, $validated['file']);

        if ($request->boolean('commit')) {
            $importJob = $this->holdingsImportService->commit($importJob);
        }

        return response()->json($importJob, 201);
    }

    public function show(Request $request, ImportJob $importJob): JsonResponse
    {
        abort_unless($importJob->user_id === $request->user()->id, 404);

        return response()->json($importJob);
    }

    public function commit(Request $request, ImportJob $importJob): JsonResponse
    {
        abort_unless($importJob->user_id === $request->user()->id, 404);

        return response()->json(
            $this->holdingsImportService->commit($importJob)
        );
    }
}
