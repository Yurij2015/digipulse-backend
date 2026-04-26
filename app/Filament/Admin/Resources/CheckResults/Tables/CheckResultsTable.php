<?php

namespace App\Filament\Admin\Resources\CheckResults\Tables;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

                TextColumn::make('site.user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->site?->user_id
                        ? UserResource::getUrl('view', ['record' => $record->site->user_id])
                        : null),

                TextColumn::make('configuration.checkType.name')
                    ->label('Check Type')
                    ->description(fn ($record) => isset($record->metadata['days_remaining'])
                        ? "Expires in {$record->metadata['days_remaining']} days"
                        : null)
                    ->sortable(),

                TextColumn::make('metadata.ip')
                    ->label('Server')
                    ->description(fn ($record) => isset($record->metadata['country'])
                        ? ($record->metadata['country'].' - '.($record->metadata['isp'] ?? ''))
                        : null)
                    ->searchable(),

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
                SelectFilter::make('status')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                        'slow' => 'Slow',
                    ])
                    ->multiple(),
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
