<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Articles\Pages;

use App\Filament\Admin\Resources\KnowledgeBase\Articles\KnowledgeBaseArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBaseArticle extends CreateRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;
}
