<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CheckTypeController;
use App\Http\Controllers\Api\Internal\InternalCheckResultController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\SiteHistoryController;
use App\Http\Middleware\InternalMonitorMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['frontend.key'])->group(function () {
    Route::middleware(['turnstile'])->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('sites', SiteController::class);
        Route::get('sites/{site}/history', [SiteHistoryController::class, 'index'])->name('sites.history');
        Route::get('/check-types', [CheckTypeController::class, 'index'])->name('check-types.index');
    });
});

Route::prefix('webhooks')->name('webhooks.')->middleware(InternalMonitorMiddleware::class)->group(function () {
    Route::post('/results', [InternalCheckResultController::class, 'store'])->name('results');
});
