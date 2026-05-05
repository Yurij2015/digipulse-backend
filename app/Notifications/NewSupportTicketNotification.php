<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\SupportTicket;
use App\Notifications\Contracts\TelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportTicketNotification extends Notification implements ShouldQueue, TelegramNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public SupportTicket $ticket) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_telegram ?? true) {
            $channels[] = TelegramChannel::class;
        }

        if ($notifiable->notify_email ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $userStr = $this->ticket->user->name ?? $this->ticket->contact_email;
        $adminUrl = rtrim(config('app.url'), '/').'/admin/support-tickets/'.$this->ticket->id;

        return (new MailMessage)
            ->subject('New Support Ticket: '.$this->ticket->subject)
            ->view('emails.new-support-ticket', [
                'ticket' => $this->ticket,
                'userStr' => $userStr,
                'url' => $adminUrl,
            ]);
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(mixed $notifiable): array
    {
        $userStr = $this->ticket->user->name ?? $this->ticket->contact_email;
        $priorityEmoji = match ($this->ticket->priority) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🔵',
            default => '⚪',
        };

        $safeUser = htmlspecialchars($userStr ?? '');
        $safeSubject = htmlspecialchars($this->ticket->subject ?? '');
        $safeMessage = htmlspecialchars($this->ticket->message ?? '');

        $text = "📩 <b>New Support Ticket!</b>\n\n".
               "<b>From:</b> {$safeUser}\n".
               "<b>Subject:</b> {$safeSubject}\n".
               "<b>Priority:</b> {$priorityEmoji} ".ucfirst($this->ticket->priority)."\n\n".
               "<b>Message:</b>\n{$safeMessage}";

        $adminUrl = rtrim(config('app.url'), '/').'/admin/support-tickets/'.$this->ticket->id.'/edit';

        return [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '📝 Answer in Telegram',
                            'callback_data' => 'support_reply:'.$this->ticket->id,
                        ],
                        [
                            'text' => '🌐 Open in Admin',
                            'url' => $adminUrl,
                        ],
                    ],
                ],
            ],
        ];
    }
}
