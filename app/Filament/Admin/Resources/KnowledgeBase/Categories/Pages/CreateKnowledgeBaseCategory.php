<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Categories\Pages;

use App\Filament\Admin\Resources\KnowledgeBase\Categories\KnowledgeBaseCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBaseCategory extends CreateRecord
{
    protected static string $resource = KnowledgeBaseCategoryResource::class;
}
