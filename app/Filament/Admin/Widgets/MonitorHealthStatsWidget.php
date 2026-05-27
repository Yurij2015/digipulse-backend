<?php

namespace App\Filament\Admin\Widgets;

use App\Models\CheckResult;
use App\Models\SiteCheckConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MonitorHealthStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            $this->checksLastHour(),
            $this->checksLast24h(),
            $this->p95Latency(),
            $this->failedQueueDepth(),
            $this->overdueConfigurations(),
            $this->serviceBlips(),
        ];
    }

    private function checksLastHour(): Stat
    {
        $count = CheckResult::where('checked_at', '>=', now()->subHour())->count();

        return Stat::make('Checks (last hour)', number_format($count))
            ->description('Results processed by consumer')
            ->descriptionIcon('heroicon-m-arrow-path')
            ->color('primary');
    }

    private function checksLast24h(): Stat
    {
        $count = CheckResult::where('checked_at', '>=', now()->subDay())->count();

        return Stat::make('Checks (24h)', number_format($count))
            ->description('Total results last 24 hours')
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color('info');
    }

    private function p95Latency(): Stat
    {
        $row = DB::selectOne("
            SELECT PERCENTILE_CONT(0.95) WITHIN GROUP (
                ORDER BY EXTRACT(EPOCH FROM (checked_at - scheduled_at)) * 1000
            ) AS p95_ms
            FROM check_results
            WHERE scheduled_at IS NOT NULL
              AND checked_at >= NOW() - INTERVAL '1 hour'
        ");

        $p95 = $row?->p95_ms !== null ? (int) $row->p95_ms : null;

        $label = $p95 !== null ? "{$p95} ms" : 'N/A';
        $color = match (true) {
            $p95 === null => 'gray',
            $p95 > 30000 => 'danger',
            $p95 > 10000 => 'warning',
            default => 'success',
        };

        return Stat::make('P95 check latency', $label)
            ->description('scheduled_at → checked_at, last hour')
            ->descriptionIcon('heroicon-m-clock')
            ->color($color);
    }

    private function failedQueueDepth(): Stat
    {
        $depth = 0;

        try {
            $depth = (int) Redis::llen('monitoring:results:failed');
        } catch (\Throwable) {
        }

        return Stat::make('Failed queue depth', $depth)
            ->description('monitoring:results:failed')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color($depth > 0 ? 'danger' : 'success');
    }

    private function overdueConfigurations(): Stat
    {
        $count = SiteCheckConfiguration::query()
            ->join('sites', 'sites.id', '=', 'site_check_configurations.site_id')
            ->where('site_check_configurations.is_active', true)
            ->where('sites.is_active', true)
            ->whereNotNull('site_check_configurations.last_checked_at')
            ->whereRaw("site_check_configurations.last_checked_at < NOW() - (sites.update_interval * 2 || ' seconds')::interval")
            ->count();

        return Stat::make('Overdue configurations', $count)
            ->description('Not checked for 2× their interval')
            ->descriptionIcon('heroicon-m-exclamation-circle')
            ->color($count > 0 ? 'warning' : 'success');
    }

    private function serviceBlips(): Stat
    {
        $count = SiteCheckConfiguration::where('consecutive_failures', '>', 0)
            ->whereNull('confirmed_down_at')
            ->count();

        return Stat::make('Unconfirmed failures', $count)
            ->description('Failing but below alert threshold')
            ->descriptionIcon('heroicon-m-signal-slash')
            ->color($count > 0 ? 'warning' : 'success');
    }
}
