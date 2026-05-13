<?php

namespace App\Providers;

use App\Domain\Monitoring\Contracts\AlertServiceInterface;
use App\Domain\Monitoring\Contracts\CachePortInterface;
use App\Domain\Monitoring\Contracts\ResultRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteStatsRepositoryInterface;
use App\Infrastructure\Monitoring\Cache\CacheService;
use App\Infrastructure\Monitoring\Notifications\NotificationService;
use App\Infrastructure\Monitoring\Repositories\EloquentResultRepository;
use App\Infrastructure\Monitoring\Repositories\EloquentSiteRepository;
use App\Infrastructure\Monitoring\Repositories\EloquentSiteStatsRepository;
use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(
            SiteRepositoryInterface::class,
            EloquentSiteRepository::class
        );

        $this->app->singleton(
            SiteManagementRepositoryInterface::class,
            EloquentSiteRepository::class
        );

        $this->app->singleton(
            ResultRepositoryInterface::class,
            EloquentResultRepository::class
        );

        $this->app->singleton(
            AlertServiceInterface::class,
            NotificationService::class
        );

        $this->app->singleton(
            CachePortInterface::class,
            CacheService::class
        );

        $this->app->singleton(
            SiteStatsRepositoryInterface::class,
            EloquentSiteStatsRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
