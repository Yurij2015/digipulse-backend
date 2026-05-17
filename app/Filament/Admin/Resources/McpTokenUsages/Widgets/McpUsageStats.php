<?php

namespace App\Filament\Admin\Resources\McpTokenUsages\Widgets;

use App\Models\McpTokenUsage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laravel\Sanctum\PersonalAccessToken;

class McpUsageStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('MCP Requests Today', McpTokenUsage::where('date', today())->sum('count'))
                ->description('Requests across all tokens')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('primary'),

            Stat::make('MCP Requests (30d)', McpTokenUsage::where('date', '>=', now()->subDays(30))->sum('count'))
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Active Tokens', PersonalAccessToken::whereHas('usages')->count())
                ->description('Tokens with at least one request')
                ->descriptionIcon('heroicon-m-key')
                ->color('success'),
        ];
    }
}
