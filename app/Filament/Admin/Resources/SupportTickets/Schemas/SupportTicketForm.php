<?php

namespace App\Filament\Admin\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(),
                TextInput::make('contact_email')
                    ->email()
                    ->placeholder('guest@example.com')
                    ->disabled(),
                TextInput::make('subject')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull()
                    ->rows(6),
                Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ])
                    ->default('open')
                    ->required(),
                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->default('medium')
                    ->required(),
                DateTimePicker::make('resolved_at')
                    ->nullable(),
            ]);
    }
}
