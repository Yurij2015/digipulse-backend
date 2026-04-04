<?php

namespace App\Filament\Admin\Resources\Sites\Schemas;

use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('url')
                    ->required()
                    ->url()
                    ->maxLength(255)
                    ->suffixIcon('heroicon-m-globe-alt'),
                \Filament\Forms\Components\Select::make('update_interval')
                    ->options([
                        60 => '1 minute',
                        300 => '5 minutes',
                        3600 => '1 hour',
                        86400 => '1 day',
                    ])
                    ->default(300)
                    ->required(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
