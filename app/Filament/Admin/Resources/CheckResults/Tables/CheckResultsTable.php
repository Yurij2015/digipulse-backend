<?php

namespace App\Filament\Admin\Resources\CheckResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CheckResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.url')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('configuration.checkType.name')
                    ->label('Check Type')
                    ->sortable(),

                IconColumn::make('status')
                    ->icon(fn (string $state): string => match ($state) {
                        'up' => 'heroicon-o-check-circle',
                        'down' => 'heroicon-o-x-circle',
                        'slow' => 'heroicon-o-exclamation-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'slow' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('response_time_ms')
                    ->label('Response (ms)')
                    ->suffix(' ms')
                    ->sortable(),

                TextColumn::make('checked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('checked_at', 'desc');
    }
}
