<?php

namespace App\Filament\Admin\Resources\Sites\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConfigurationsRelationManager extends RelationManager
{
    protected static string $relationship = 'configurations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('check_type_id')
                    ->relationship('checkType', 'name')
                    ->required()
                    ->native(false),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                KeyValue::make('params')
                    ->addColumnLabel('Parameter')
                    ->addValueLabel('Value')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('checkType.name')
                    ->label('Type')
                    ->badge(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('last_status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'slow' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('last_checked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
