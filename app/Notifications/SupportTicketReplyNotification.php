<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\SupportTicketMessage;
use App\Notifications\Contracts\TelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReplyNotification extends Notification implements ShouldQueue, TelegramNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public SupportTicketMessage $reply) {}

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
        $ticket = $this->reply->ticket;
        $author = $this->reply->user->name ?? 'Support Agent';

        $url = $this->reply->is_admin_reply
            ? rtrim(config('app.frontend_url', config('app.url')), '/').'/support'
            : rtrim(config('app.url'), '/').'/admin/support-tickets/'.$ticket->id;

        return (new MailMessage)
            ->subject('New reply to your support ticket: '.$ticket->subject)
            ->view('emails.support-ticket-reply', [
                'ticket' => $ticket,
                'reply' => $this->reply,
                'author' => $author,
                'url' => $url,
            ]);
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(mixed $notifiable): array
    {
        $ticket = $this->reply->ticket;
        $author = $this->reply->user->name ?? 'Guest';

        $typeStr = $this->reply->is_admin_reply ? "🛠 <b>Support Reply!</b>" : "💬 <b>User Reply!</b>";

        $safeSubject = htmlspecialchars($ticket->subject ?? '');
        $safeAuthor = htmlspecialchars($author ?? '');
        $safeMessage = htmlspecialchars($this->reply->message ?? '');

        $text = "{$typeStr}\n\n".
               "<b>Ticket:</b> {$safeSubject}\n".
               "<b>From:</b> {$safeAuthor}\n\n".
               "<b>Message:</b>\n{$safeMessage}";

        // If it's a user reply, show admin link. If admin reply, show frontend link.
        if ($this->reply->is_admin_reply) {
            $url = rtrim(config('app.frontend_url', config('app.url')), '/').'/support';
            $btnText = '🌐 View in Support';
        } else {
            $url = rtrim(config('app.url'), '/').'/admin/support-tickets/'.$ticket->id.'/edit';
            $btnText = '🌐 Open in Admin';
        }

        return [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => $btnText,
                            'url' => $url,
                        ],
                    ],
                ],
            ],
        ];
    }
}
