<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url', config('app.url')).'/support';

        return (new MailMessage)
            ->subject('Your support ticket has been closed: '.$this->ticket->subject)
            ->view('emails.support-ticket-closed', [
                'ticket' => $this->ticket,
                'url' => $url,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'message' => 'Your ticket has been closed by an administrator.',
            'format' => 'filament',
        ];
    }
}
