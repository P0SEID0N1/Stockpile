<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ImportPageController;
use App\Http\Controllers\Web\SessionController;
use App\Http\Controllers\Web\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    Route::get('/setup', [SetupController::class, 'create'])->name('setup');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/portfolio', [DashboardController::class, 'portfolio'])->name('portfolio.index');
    Route::get('/performance', [DashboardController::class, 'performance'])->name('performance.index');
    Route::get('/journal', [DashboardController::class, 'journal'])->name('journal.index');
    Route::get('/plan', [DashboardController::class, 'plan'])->name('plan.index');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings.index');
    Route::get('/imports', [ImportPageController::class, 'index'])->name('imports.index');
    Route::post('/imports', [ImportPageController::class, 'store'])->name('imports.store');
    Route::post('/imports/{importJob}/commit', [ImportPageController::class, 'commit'])->name('imports.commit');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});
