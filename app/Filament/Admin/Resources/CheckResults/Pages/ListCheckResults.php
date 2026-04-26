<?php

namespace App\Filament\Admin\Resources\CheckResults\Pages;

use App\Filament\Admin\Resources\CheckResults\CheckResultResource;
use App\Filament\Admin\Resources\CheckResults\Widgets\CheckResultsStats;
use App\Filament\Admin\Resources\CheckResults\Widgets\ResponseTimeChart;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCheckResults extends ListRecords
{
    protected static string $resource = CheckResultResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CheckResultsStats::class,
            ResponseTimeChart::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'issues' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['down', 'slow'])),
            'all' => Tab::make(),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'issues';
    }
}
