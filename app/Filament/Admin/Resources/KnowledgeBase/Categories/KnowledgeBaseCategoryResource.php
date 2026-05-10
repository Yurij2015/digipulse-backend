<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Categories;

use App\Filament\Admin\Resources\KnowledgeBase\Categories\Pages\CreateKnowledgeBaseCategory;
use App\Filament\Admin\Resources\KnowledgeBase\Categories\Pages\EditKnowledgeBaseCategory;
use App\Filament\Admin\Resources\KnowledgeBase\Categories\Pages\ListKnowledgeBaseCategories;
use App\Filament\Admin\Resources\KnowledgeBase\Categories\Schemas\KnowledgeBaseCategoryForm;
use App\Filament\Admin\Resources\KnowledgeBase\Categories\Tables\KnowledgeBaseCategoriesTable;
use App\Models\KnowledgeBaseCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class KnowledgeBaseCategoryResource extends Resource
{
    protected static ?string $model = KnowledgeBaseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static UnitEnum|string|null $navigationGroup = 'Knowledge Base';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Category';

    protected static ?string $pluralLabel = 'Categories';

    public static function form(Schema $schema): Schema
    {
        return KnowledgeBaseCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeBaseCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeBaseCategories::route('/'),
            'create' => CreateKnowledgeBaseCategory::route('/create'),
            'edit' => EditKnowledgeBaseCategory::route('/{record}/edit'),
        ];
    }
}
