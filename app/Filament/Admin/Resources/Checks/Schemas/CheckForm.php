<?php

namespace App\Filament\Admin\Resources\Checks\Schemas;

use Filament\Schemas\Schema;

class CheckForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('site_check_configuration_id')
                    ->relationship('configuration', 'id')
                    ->label('Configuration')
                    ->disabled(),
                \Filament\Forms\Components\Toggle::make('is_successful')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('response_time')
                    ->suffix('ms')
                    ->disabled(),
                \Filament\Forms\Components\KeyValue::make('results')
                    ->columnSpanFull()
                    ->disabled(),
                \Filament\Forms\Components\Textarea::make('error_message')
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }
}
