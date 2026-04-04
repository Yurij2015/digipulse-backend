<?php

namespace App\Filament\Admin\Resources\CheckTypes\Pages;

use App\Filament\Admin\Resources\CheckTypes\CheckTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckType extends EditRecord
{
    protected static string $resource = CheckTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
