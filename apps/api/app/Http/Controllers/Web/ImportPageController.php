<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithPortfolio;
use App\Models\ImportJob;
use App\Services\HoldingsImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportPageController extends Controller
{
    use InteractsWithPortfolio;

    public function __construct(
        private readonly HoldingsImportService $holdingsImportService,
    ) {
    }

    public function index(Request $request)
    {
        $portfolio = $this->resolvePortfolio($request);

        return view('imports.index', [
            'portfolio' => $portfolio,
            'imports' => $portfolio->importJobs()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $portfolio = $this->resolvePortfolio($request);
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $importJob = $this->holdingsImportService->preview($request->user(), $portfolio, $validated['file']);

        return redirect()->route('imports.index')->with('import_job_id', $importJob->id);
    }

    public function commit(Request $request, ImportJob $importJob): RedirectResponse
    {
        abort_unless($importJob->user_id === $request->user()->id, 404);
        $this->holdingsImportService->commit($importJob);

        return redirect()->route('imports.index')->with('status', 'Import committed.');
    }
}
