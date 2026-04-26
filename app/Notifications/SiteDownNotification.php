<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\Site;
use App\Notifications\Contracts\TelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteDownNotification extends Notification implements ShouldQueue, TelegramNotification
{
    use Queueable;

    private Site $site;

    /**
     * Create a new notification instance.
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * Get the notification's delivery channels.
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
        return (new MailMessage)
            ->error()
            ->subject('🔴 CRITICAL: Your site is down!')
            ->greeting('Hello!')
            ->line("Your site **{$this->site->name}** ({$this->site->url}) is currently unreachable.")
            ->line('The latest check recorded the status: **down**.')
            ->action('View Site Dashboard', rtrim(config('app.frontend_url', config('app.url')), '/').'/dashboard')
            ->line('Please check your server as soon as possible.');
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(mixed $notifiable): string
    {
        return "🔴 **WARNING: Site is offline!**\n\n".
               "Your site **{$this->site->name}** ({$this->site->url}) is currently unreachable.\n\n".
               'The latest check recorded the status: `down`.';
    }
}
