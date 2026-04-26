<?php

namespace App\Filament\Admin\Resources\CheckResults\Widgets;

use App\Models\CheckResult;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CheckResultsStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Failed Checks (24h)', CheckResult::where('status', 'down')
                ->where('checked_at', '>=', now()->subDay())->count())
                ->description('Total downtime events')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            Stat::make('Slow Checks (24h)', CheckResult::where('status', 'slow')
                ->where('checked_at', '>=', now()->subDay())->count())
                ->description('Performance issues')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
            Stat::make('Total Checks (24h)', CheckResult::where('checked_at', '>=', now()->subDay())->count())
                ->description('Monitoring activity')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}
