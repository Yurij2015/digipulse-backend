<?php

namespace App\Filament\Admin\Resources\Checks;

use App\Filament\Admin\Resources\Checks\Pages\CreateCheck;
use App\Filament\Admin\Resources\Checks\Pages\EditCheck;
use App\Filament\Admin\Resources\Checks\Pages\ListChecks;
use App\Filament\Admin\Resources\Checks\Pages\ViewCheck;
use App\Filament\Admin\Resources\Checks\Schemas\CheckForm;
use App\Filament\Admin\Resources\Checks\Schemas\CheckInfolist;
use App\Filament\Admin\Resources\Checks\Tables\ChecksTable;
use App\Models\Check;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CheckResource extends Resource
{
    protected static ?string $model = Check::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CheckForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CheckInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecksTable::configure($table);
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
            'index' => ListChecks::route('/'),
            'create' => CreateCheck::route('/create'),
            'view' => ViewCheck::route('/{record}'),
            'edit' => EditCheck::route('/{record}/edit'),
        ];
    }
}
