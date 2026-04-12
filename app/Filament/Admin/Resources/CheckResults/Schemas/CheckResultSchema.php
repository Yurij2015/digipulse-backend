<?php

namespace App\Filament\Admin\Resources\CheckResults\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CheckResultSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('site.url')
                    ->label('Site URL')
                    ->disabled(),
                TextInput::make('configuration.checkType.name')
                    ->label('Check Type')
                    ->disabled(),
                TextInput::make('status')
                    ->disabled(),
                TextInput::make('response_time_ms')
                    ->label('Response Time')
                    ->suffix(' ms')
                    ->disabled(),
                DateTimePicker::make('checked_at')
                    ->disabled(),
                TextInput::make('error_message')
                    ->label('Error Message')
                    ->disabled()
                    ->columnSpanFull(),
                KeyValue::make('metadata')
                    ->label('Metadata')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
