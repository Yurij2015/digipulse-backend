<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(TelescopeApplicationServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', static function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('register', static function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        ResetPassword::createUrlUsing(static function ($user, string $token) {
            return rtrim(config('app.frontend_url'), '/').'/auth/reset-password?token='.$token.'&email='.urlencode($user->email);
        });
    }
}
