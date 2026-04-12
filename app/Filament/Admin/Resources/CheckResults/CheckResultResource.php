<?php

namespace App\Filament\Admin\Resources\CheckResults;

use App\Filament\Admin\Resources\CheckResults\Pages\ListCheckResults;
use App\Filament\Admin\Resources\CheckResults\Pages\ViewCheckResult;
use App\Filament\Admin\Resources\CheckResults\Schemas\CheckResultSchema;
use App\Filament\Admin\Resources\CheckResults\Tables\CheckResultsTable;
use App\Models\CheckResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CheckResultResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return CheckResultSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckResultsTable::configure($table);
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
            'index' => ListCheckResults::route('/'),
            'view' => ViewCheckResult::route('/{record}'),
        ];
    }
}
