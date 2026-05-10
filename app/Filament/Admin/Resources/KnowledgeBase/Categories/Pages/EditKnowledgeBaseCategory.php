<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Categories\Pages;

use App\Filament\Admin\Resources\KnowledgeBase\Categories\KnowledgeBaseCategoryResource;
use App\Models\KnowledgeBaseCategory;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read KnowledgeBaseCategory $record
 */
class EditKnowledgeBaseCategory extends EditRecord
{
    protected static string $resource = KnowledgeBaseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['name', 'description'] as $field) {
            $data[$field] = $this->record->getTranslations($field);
        }

        return $data;
    }
}
