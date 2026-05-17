<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\CheckTypeController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\KnowledgeBaseController;
use App\Http\Controllers\Api\V1\Mcp\IncidentsController;
use App\Http\Controllers\Api\V1\Mcp\OverviewController;
use App\Http\Controllers\Api\V1\Mcp\SiteHistoryController as McpSiteHistoryController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\SiteHistoryController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\TelegramController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::middleware('frontend.key')->get('/health', HealthController::class)->name('health');

    Route::middleware(['frontend.key'])->group(function () {
        Route::middleware(['turnstile'])->group(function () {
            Route::post('/register', [AuthController::class, 'register'])
                ->middleware('throttle:register')
                ->name('register');

            Route::post('/login', [AuthController::class, 'login'])
                ->middleware('throttle:login')
                ->name('login');

            Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
                ->middleware('throttle:login')
                ->name('password.email');

            Route::post('/reset-password', [PasswordResetController::class, 'reset'])
                ->middleware('throttle:login')
                ->name('password.update');
        });

        Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->name('verification.verify');

        Route::prefix('knowledge-base')->name('knowledge-base.')->group(function () {
            Route::get('/categories', [KnowledgeBaseController::class, 'categories'])->name('categories');
            Route::get('/categories/{slug}', [KnowledgeBaseController::class, 'category'])->name('category');
            Route::get('/articles/{slug}', [KnowledgeBaseController::class, 'article'])->name('article');
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationNotification'])
                ->middleware(['throttle:6,1'])
                ->name('verification.send');

            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
            Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

            Route::apiResource('sites', SiteController::class)->except(['store', 'destroy']);
            Route::post('sites', [SiteController::class, 'store'])
                ->middleware('throttle:sites')
                ->name('sites.store');
            Route::delete('sites/{site}', [SiteController::class, 'destroy'])
                ->middleware('throttle:sites')
                ->name('sites.destroy');
            Route::get('sites/{site}/history', [SiteHistoryController::class, 'index'])->name('sites.history');

            Route::apiResource('projects', ProjectController::class);

            Route::get('/check-types', [CheckTypeController::class, 'index'])->name('check-types.index');

            Route::get('/support/tickets', [SupportTicketController::class, 'index'])->name('support.tickets.index');
            Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
            Route::post('/support/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])
                ->middleware('throttle:support')
                ->name('support.tickets.reply');
            Route::post('/support/tickets', [SupportTicketController::class, 'store'])
                ->middleware('throttle:support')
                ->name('support.tickets.store');

            Route::get('/telegram/connect', [TelegramController::class, 'connect'])->name('telegram.connect');
            Route::post('/telegram/disconnect', [TelegramController::class, 'disconnect'])->name('telegram.disconnect');

            Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
            Route::post('/tokens', [TokenController::class, 'store'])->name('tokens.store');
            Route::delete('/tokens/{id}', [TokenController::class, 'destroy'])->name('tokens.destroy');

            Route::prefix('mcp')->name('mcp.')->group(function () {
                Route::get('/overview', OverviewController::class)->name('overview');
                Route::get('/sites/{siteId}/history', McpSiteHistoryController::class)->name('sites.history');
                Route::get('/incidents', IncidentsController::class)->name('incidents');
            });
        });
    });
});

Route::post('/webhooks/telegram', [TelegramController::class, 'webhook'])->name('webhooks.telegram');
