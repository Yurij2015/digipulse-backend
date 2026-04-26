<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('notify_email')
                    ->label('Email')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('notify_telegram')
                    ->label('Telegram')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('google_id')
                    ->label('Google ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('telegram_chat_id')
                    ->label('Telegram Chat ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('roles')
                    ->label('Roles')
                    ->formatStateUsing(fn ($state, $record) => $record->roles->pluck('name')->join(', '))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // add filters if needed
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
