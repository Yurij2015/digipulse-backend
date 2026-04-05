<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['frontend.key'])->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('sites', SiteController::class)->only(['index', 'store']);
        Route::get('/check-types', [\App\Http\Controllers\Api\CheckTypeController::class, 'index'])->name('check-types.index');
    });
});
