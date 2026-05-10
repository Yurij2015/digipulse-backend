<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Categories\Pages;

use App\Filament\Admin\Resources\KnowledgeBase\Categories\KnowledgeBaseCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBaseCategories extends ListRecords
{
    protected static string $resource = KnowledgeBaseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
