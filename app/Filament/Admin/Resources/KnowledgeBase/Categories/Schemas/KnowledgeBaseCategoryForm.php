<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class KnowledgeBaseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Translations')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('EN')
                            ->schema([
                                TextInput::make('name.en')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

                                Textarea::make('description.en')
                                    ->label('Description')
                                    ->rows(3)
                                    ->maxLength(500),
                            ]),

                        Tab::make('UK')
                            ->schema([
                                TextInput::make('name.uk')
                                    ->label('Name')
                                    ->maxLength(255),

                                Textarea::make('description.uk')
                                    ->label('Description')
                                    ->rows(3)
                                    ->maxLength(500),
                            ]),

                        Tab::make('PL')
                            ->schema([
                                TextInput::make('name.pl')
                                    ->label('Name')
                                    ->maxLength(255),

                                Textarea::make('description.pl')
                                    ->label('Description')
                                    ->rows(3)
                                    ->maxLength(500),
                            ]),
                    ]),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash']),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ]);
    }
}
