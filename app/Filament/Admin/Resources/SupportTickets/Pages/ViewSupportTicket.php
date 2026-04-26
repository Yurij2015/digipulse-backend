<?php

namespace App\Filament\Admin\Resources\SupportTickets\Pages;

use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use App\Notifications\SupportTicketClosedNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('close')
                ->label('Close Ticket')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn ($record) => $record->status === 'closed')
                ->action(function ($record) {
                    $record->update(['status' => 'closed']);

                    $record->messages()->create([
                        'user_id' => Auth::id(),
                        'message' => 'This ticket has been closed by an administrator. If you have further questions or the problem persists, please create a new support ticket.',
                        'is_admin_reply' => true,
                    ]);

                    if ($record->user) {
                        $record->user->notify(new SupportTicketClosedNotification($record));
                    }

                    Notification::make()
                        ->title('Ticket closed successfully')
                        ->success()
                        ->send();
                }),
        ];
    }
}
