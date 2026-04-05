<?php

namespace App\Filament\Admin\Resources\Sites\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->required()
                    ->url()
                    ->maxLength(255)
                    ->suffixIcon('heroicon-m-globe-alt'),
                Select::make('update_interval')
                    ->options([
                        60 => '1 minute',
                        300 => '5 minutes',
                        3600 => '1 hour',
                        86400 => '1 day',
                    ])
                    ->default(300)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
