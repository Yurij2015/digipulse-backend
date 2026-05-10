<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Articles;

use App\Filament\Admin\Resources\KnowledgeBase\Articles\Pages\CreateKnowledgeBaseArticle;
use App\Filament\Admin\Resources\KnowledgeBase\Articles\Pages\EditKnowledgeBaseArticle;
use App\Filament\Admin\Resources\KnowledgeBase\Articles\Pages\ListKnowledgeBaseArticles;
use App\Filament\Admin\Resources\KnowledgeBase\Articles\Schemas\KnowledgeBaseArticleForm;
use App\Filament\Admin\Resources\KnowledgeBase\Articles\Tables\KnowledgeBaseArticlesTable;
use App\Models\KnowledgeBaseArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class KnowledgeBaseArticleResource extends Resource
{
    protected static ?string $model = KnowledgeBaseArticle::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Knowledge Base';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Article';

    protected static ?string $pluralLabel = 'Articles';

    public static function form(Schema $schema): Schema
    {
        return KnowledgeBaseArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeBaseArticlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeBaseArticles::route('/'),
            'create' => CreateKnowledgeBaseArticle::route('/create'),
            'edit' => EditKnowledgeBaseArticle::route('/{record}/edit'),
        ];
    }
}
