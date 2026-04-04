<?php

namespace App\Filament\Admin\Resources\CheckTypes;

use App\Filament\Admin\Resources\CheckTypes\Pages\CreateCheckType;
use App\Filament\Admin\Resources\CheckTypes\Pages\EditCheckType;
use App\Filament\Admin\Resources\CheckTypes\Pages\ListCheckTypes;
use App\Filament\Admin\Resources\CheckTypes\Schemas\CheckTypeForm;
use App\Filament\Admin\Resources\CheckTypes\Tables\CheckTypesTable;
use App\Models\CheckType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CheckTypeResource extends Resource
{
    protected static ?string $model = CheckType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CheckTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCheckTypes::route('/'),
            'create' => CreateCheckType::route('/create'),
            'edit' => EditCheckType::route('/{record}/edit'),
        ];
    }
}
