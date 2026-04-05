<?php

namespace App\Filament\Admin\Resources\Checks\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CheckForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('site_check_configuration_id')
                    ->relationship('configuration', 'id')
                    ->label('Configuration')
                    ->disabled(),
                Toggle::make('is_successful')
                    ->disabled(),
                TextInput::make('response_time')
                    ->suffix('ms')
                    ->disabled(),
                KeyValue::make('results')
                    ->columnSpanFull()
                    ->disabled(),
                Textarea::make('error_message')
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }
}
