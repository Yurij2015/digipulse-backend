<?php

namespace App\Filament\Admin\Resources\Checks\Schemas;

use Filament\Schemas\Schema;

class CheckInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\TextEntry::make('configuration.site.name')
                    ->label('Site'),
                \Filament\Infolists\Components\TextEntry::make('configuration.checkType.name')
                    ->label('Type')
                    ->badge(),
                \Filament\Infolists\Components\IconEntry::make('is_successful')
                    ->boolean(),
                \Filament\Infolists\Components\TextEntry::make('response_time')
                    ->suffix(' ms'),
                \Filament\Infolists\Components\TextEntry::make('created_at')
                    ->label('Checked At')
                    ->dateTime(),
                \Filament\Infolists\Components\KeyValueEntry::make('results')
                    ->columnSpanFull(),
                \Filament\Infolists\Components\TextEntry::make('error_message')
                    ->columnSpanFull()
                    ->color('danger'),
            ]);
    }
}
