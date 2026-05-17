<?php

namespace App\Filament\Admin\Resources\McpTokenUsages;

use App\Filament\Admin\Resources\McpTokenUsages\Pages\ListMcpTokenUsages;
use App\Filament\Admin\Resources\McpTokenUsages\Tables\McpTokenUsagesTable;
use App\Models\McpTokenUsage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class McpTokenUsageResource extends Resource
{
    protected static ?string $model = McpTokenUsage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'MCP';

    protected static ?string $navigationLabel = 'Usage';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return McpTokenUsagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMcpTokenUsages::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
