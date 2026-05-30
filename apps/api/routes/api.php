<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AllocationTargetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BenchmarkController;
use App\Http\Controllers\Api\HoldingController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\PortfolioController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth.api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('portfolios', PortfolioController::class)->only(['index', 'store']);
    Route::post('/portfolios/{id}/reset', [PortfolioController::class, 'reset']);
    Route::apiResource('accounts', AccountController::class)->only(['index', 'store', 'update']);
    Route::apiResource('holdings', HoldingController::class)->only(['index', 'store', 'update']);
    Route::get('/performance/summary', [PerformanceController::class, 'summary']);
    Route::get('/performance/timeseries', [PerformanceController::class, 'timeseries']);
    Route::get('/benchmarks', [BenchmarkController::class, 'index']);
    Route::post('/benchmarks/select', [BenchmarkController::class, 'select']);
    Route::apiResource('journal', JournalController::class)
    ->only(['index', 'store'])
    ->names([
        'index' => 'api.journal.index',
        'store' => 'api.journal.store',
    ]);
    Route::get('/plans/targets', [AllocationTargetController::class, 'index']);
    Route::put('/plans/targets', [AllocationTargetController::class, 'update']);
    Route::post('/imports/holdings-csv', [ImportController::class, 'store']);
    Route::post('/imports/{importJob}/commit', [ImportController::class, 'commit']);
    Route::get('/imports/{importJob}', [ImportController::class, 'show']);
});
