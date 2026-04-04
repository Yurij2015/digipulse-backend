<?php

namespace App\Filament\Admin\Resources\Checks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class ChecksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('configuration.site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('configuration.checkType.name')
                    ->label('Type')
                    ->badge(),
                \Filament\Tables\Columns\IconColumn::make('is_successful')
                    ->boolean()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('response_time')
                    ->label('Response Time')
                    ->suffix(' ms')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Checked At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
