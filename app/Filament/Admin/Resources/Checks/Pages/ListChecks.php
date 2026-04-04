<?php

namespace App\Filament\Admin\Resources\Checks\Pages;

use App\Filament\Admin\Resources\Checks\CheckResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChecks extends ListRecords
{
    protected static string $resource = CheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
