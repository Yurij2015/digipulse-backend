<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\MonitorHealthStatsWidget;
use App\Filament\Admin\Widgets\OverdueConfigurationsWidget;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class MonitorHealthPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Monitor Health';

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Monitor Health';

    protected static ?string $slug = 'monitor-health';

    public function getView(): string
    {
        return 'filament.admin.pages.monitor-health';
    }

    public function getWidgets(): array
    {
        return [
            MonitorHealthStatsWidget::class,
            OverdueConfigurationsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
