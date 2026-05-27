<?php

namespace App\Filament\Admin\Widgets;

use App\Models\SiteCheckConfiguration;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class OverdueConfigurationsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Overdue Configurations';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.url')
                    ->label('URL')
                    ->limit(40)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('checkType.name')
                    ->label('Check type')
                    ->badge(),

                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Last checked')
                    ->dateTime()
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('overdue_seconds')
                    ->label('Overdue by')
                    ->state(function (SiteCheckConfiguration $record): string {
                        if (! $record->last_checked_at) {
                            return 'Never checked';
                        }
                        $seconds = now()->diffInSeconds($record->last_checked_at);
                        if ($seconds >= 3600) {
                            return round($seconds / 3600, 1).'h';
                        }
                        if ($seconds >= 60) {
                            return round($seconds / 60).'m';
                        }

                        return "{$seconds}s";
                    })
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('consecutive_failures')
                    ->label('Failures')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success'),
            ])
            ->defaultSort('last_checked_at', 'asc')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading('All configurations are on schedule')
            ->emptyStateDescription('No overdue checks detected.');
    }

    private function getQuery(): Builder
    {
        return SiteCheckConfiguration::query()
            ->with(['site', 'checkType'])
            ->join('sites', 'sites.id', '=', 'site_check_configurations.site_id')
            ->where('site_check_configurations.is_active', true)
            ->where('sites.is_active', true)
            ->whereNotNull('site_check_configurations.last_checked_at')
            ->whereRaw("site_check_configurations.last_checked_at < NOW() - (sites.update_interval * 2 || ' seconds')::interval")
            ->select('site_check_configurations.*');
    }
}
