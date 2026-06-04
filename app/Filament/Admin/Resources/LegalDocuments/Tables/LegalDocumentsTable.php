<?php

namespace App\Filament\Admin\Resources\LegalDocuments\Tables;

use App\Enums\LegalDocumentType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Document')
                    ->formatStateUsing(fn ($state) => $state instanceof LegalDocumentType
                        ? $state->label()
                        : (LegalDocumentType::tryFrom((string) $state)?->label() ?? $state)),

                IconColumn::make('published_at')
                    ->label('Published')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->published_at !== null && $record->published_at->isPast()),

                TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('type')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
