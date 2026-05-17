<?php

namespace App\Filament\Admin\Resources\McpTokenUsages\Tables;

use App\Models\McpTokenUsage;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class McpTokenUsagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('token.name')
                    ->label('Token')
                    ->searchable(),

                TextColumn::make('endpoint')
                    ->badge()
                    ->sortable(),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('count')
                    ->label('Requests')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('endpoint')
                    ->options(fn () => McpTokenUsage::distinct()->pluck('endpoint', 'endpoint')),

                Filter::make('date_from')
                    ->label('From date')
                    ->form([
                        DatePicker::make('date_from'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['date_from'],
                        fn ($q) => $q->where('date', '>=', $data['date_from'])
                    )),

                Filter::make('date_to')
                    ->label('To date')
                    ->form([
                        DatePicker::make('date_to'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['date_to'],
                        fn ($q) => $q->where('date', '<=', $data['date_to'])
                    )),
            ]);
    }
}
