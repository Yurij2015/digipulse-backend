<?php

namespace App\Filament\Admin\Resources\CheckTypes\Pages;

use App\Filament\Admin\Resources\CheckTypes\CheckTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckTypes extends ListRecords
{
    protected static string $resource = CheckTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
