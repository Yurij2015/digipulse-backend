<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Articles\Pages;

use App\Filament\Admin\Resources\KnowledgeBase\Articles\KnowledgeBaseArticleResource;
use App\Models\KnowledgeBaseArticle;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read KnowledgeBaseArticle $record
 */
class EditKnowledgeBaseArticle extends EditRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['title', 'excerpt', 'content'] as $field) {
            $data[$field] = $this->record->getTranslations($field);
        }

        return $data;
    }
}
