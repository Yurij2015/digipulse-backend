<?php

namespace App\Filament\Admin\Resources\McpTokenUsages\Pages;

use App\Filament\Admin\Resources\McpTokenUsages\McpTokenUsageResource;
use App\Filament\Admin\Resources\McpTokenUsages\Widgets\McpUsageStats;
use Filament\Resources\Pages\ListRecords;

class ListMcpTokenUsages extends ListRecords
{
    protected static string $resource = McpTokenUsageResource::class;

    protected function getHeaderWidgets(): array
    {
        return [McpUsageStats::class];
    }
}
