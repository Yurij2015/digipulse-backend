<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Resources\Users\Widgets\UserCheckStats;
use App\Filament\Admin\Resources\Users\Widgets\UserResponseTimeChart;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected string|Width|null $maxContentWidth = 'full';

    protected function getFooterWidgets(): array
    {
        if (! $this->record->sites()->exists()) {
            return [];
        }

        return [
            UserCheckStats::class,
            UserResponseTimeChart::class,
        ];
    }
}
