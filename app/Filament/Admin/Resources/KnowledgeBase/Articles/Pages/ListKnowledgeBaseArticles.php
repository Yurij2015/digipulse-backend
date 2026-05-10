<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Articles\Pages;

use App\Filament\Admin\Resources\KnowledgeBase\Articles\KnowledgeBaseArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBaseArticles extends ListRecords
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
