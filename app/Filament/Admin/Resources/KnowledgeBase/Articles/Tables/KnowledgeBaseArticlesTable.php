<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Articles\Tables;

use App\Models\KnowledgeBaseCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KnowledgeBaseArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->disk('minio')
                    ->visibility('public')
                    ->width(60)
                    ->height(40),

                TextColumn::make('title')
                    ->limit(50),

                TextColumn::make('category.name')
                    ->badge(),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('knowledge_base_category_id')
                    ->label('Category')
                    ->options(KnowledgeBaseCategory::orderBy('sort_order')->get()->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
