<?php
 
 namespace App\Filament\Admin\Resources\Sites\RelationManagers;
 
 use Filament\Actions\CreateAction;
 use Filament\Actions\DeleteAction;
 use Filament\Actions\EditAction;
 use Filament\Actions\BulkActionGroup;
 use Filament\Actions\DeleteBulkAction;
 use Filament\Resources\RelationManagers\RelationManager;
 use Filament\Schemas\Schema;
 use Filament\Tables\Table;
 
 class ConfigurationsRelationManager extends RelationManager
 {
     protected static string $relationship = 'configurations';
 
     public function form(Schema $schema): Schema
     {
         return $schema
             ->components([
                 \Filament\Forms\Components\Select::make('check_type_id')
                     ->relationship('checkType', 'name')
                     ->required()
                     ->native(false),
                 \Filament\Forms\Components\Toggle::make('is_active')
                     ->default(true)
                     ->required(),
                 \Filament\Forms\Components\KeyValue::make('params')
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
                 \Filament\Tables\Columns\TextColumn::make('checkType.name')
                     ->label('Type')
                     ->badge(),
                 \Filament\Tables\Columns\IconColumn::make('is_active')
                     ->boolean(),
                 \Filament\Tables\Columns\TextColumn::make('last_status')
                     ->badge()
                     ->color(fn (?string $state): string => match ($state) {
                         'success' => 'success',
                         'error' => 'danger',
                         default => 'gray',
                     }),
                 \Filament\Tables\Columns\TextColumn::make('last_checked_at')
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
