<?php

namespace App\Filament\Admin\Resources\LegalDocuments;

use App\Filament\Admin\Resources\LegalDocuments\Pages\EditLegalDocument;
use App\Filament\Admin\Resources\LegalDocuments\Pages\ListLegalDocuments;
use App\Filament\Admin\Resources\LegalDocuments\Schemas\LegalDocumentForm;
use App\Filament\Admin\Resources\LegalDocuments\Tables\LegalDocumentsTable;
use App\Models\LegalDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class LegalDocumentResource extends Resource
{
    protected static ?string $model = LegalDocument::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 10;

    protected static ?string $label = 'Legal Document';

    protected static ?string $pluralLabel = 'Legal Documents';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return LegalDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalDocuments::route('/'),
            'edit' => EditLegalDocument::route('/{record}/edit'),
        ];
    }
}
