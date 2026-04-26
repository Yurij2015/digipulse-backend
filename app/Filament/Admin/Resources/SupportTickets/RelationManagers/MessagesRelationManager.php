<?php

namespace App\Filament\Admin\Resources\SupportTickets\RelationManagers;

use App\Events\MessageSent;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketReplyNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public function canCreate(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull()
                    ->label('Message'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->recordTitleAttribute('message')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Author')
                    ->default('Guest'),
                TextColumn::make('message')
                    ->wrap()
                    ->limit(100),
                TextColumn::make('is_admin_reply')
                    ->label('Type')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Admin Reply' : 'User Message')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'info'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Sent At'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('reply')
                    ->label('Reply')
                    ->form([
                        Textarea::make('message')
                            ->required()
                            ->columnSpanFull()
                            ->label('Message'),
                    ])
                    ->action(function (array $data) {
                        /** @var SupportTicket $ticket */
                        $ticket = $this->getOwnerRecord();

                        $message = $ticket->messages()->create([
                            'user_id' => Auth::id(),
                            'message' => $data['message'],
                            'is_admin_reply' => true,
                        ]);

                        $ticket->update(['status' => 'in_progress']);

                        // Notify User
                        if ($ticket->user) {
                            $ticket->user->notify(new SupportTicketReplyNotification($message));
                        }

                        // Broadcast Event
                        broadcast(new MessageSent($message))->toOthers();
                    })
                    ->visible(true),
            ])
            ->recordActions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
