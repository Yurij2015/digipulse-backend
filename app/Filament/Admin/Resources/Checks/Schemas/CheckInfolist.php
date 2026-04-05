<?php

namespace App\Filament\Admin\Resources\Checks\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CheckInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('configuration.site.name')
                    ->label('Site'),
                TextEntry::make('configuration.checkType.name')
                    ->label('Type')
                    ->badge(),
                IconEntry::make('is_successful')
                    ->boolean(),
                TextEntry::make('response_time')
                    ->suffix(' ms'),
                TextEntry::make('created_at')
                    ->label('Checked At')
                    ->dateTime(),
                KeyValueEntry::make('results')
                    ->columnSpanFull(),
                TextEntry::make('error_message')
                    ->columnSpanFull()
                    ->color('danger'),
            ]);
    }
}
