<?php

namespace App\Filament\Admin\Resources\Users\Widgets;

use App\Models\CheckResult;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class UserCheckStats extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $siteIds = $this->record->sites()->pluck('id');

        $query = CheckResult::whereHas('configuration', static function ($query) use ($siteIds) {
            $query->whereIn('site_id', $siteIds);
        })->where('checked_at', '>=', now()->subDay());

        return [
            Stat::make('Failed (24h)', (clone $query)->where('status', 'down')->count())
                ->description('Recent downtime events')
                ->color('danger'),
            Stat::make('Slow (24h)', (clone $query)->where('status', 'slow')->count())
                ->description('Performance issues')
                ->color('warning'),
            Stat::make('Total Sites', $this->record->sites()->count())
                ->description('Monitored assets')
                ->color('info'),
        ];
    }
}
