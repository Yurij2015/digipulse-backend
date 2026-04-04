<?php

namespace App\Filament\Admin\Resources\Checks\Pages;

use App\Filament\Admin\Resources\Checks\CheckResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCheck extends ViewRecord
{
    protected static string $resource = CheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
