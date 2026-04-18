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
                TextInput::make('site_url')
                    ->label('Site URL')
                    ->afterStateHydrated(fn ($component, $record) => $component->state($record?->site?->url))
                    ->disabled(),
                TextInput::make('check_type_name')
                    ->label('Check Type')
                    ->afterStateHydrated(fn ($component, $record) => $component->state($record?->configuration?->checkType?->name))
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
                    ->columnSpanFull()
                    ->visible(fn ($record) => ! empty($record?->error_message)),
                KeyValue::make('metadata')
                    ->label('Metadata')
                    ->disabled()
                    ->columnSpanFull()
                    ->visible(fn ($record) => ! empty($record?->metadata)),
            ]);
    }
}
