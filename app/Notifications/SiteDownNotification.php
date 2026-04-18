<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SiteDownNotification extends Notification implements ShouldQueue
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
        return [TelegramChannel::class];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): string
    {
        return "🔴 **WARNING: Site is offline!**\n\n".
               "Your site **{$this->site->name}** ({$this->site->url}) is currently unreachable.\n\n".
               'The latest check recorded the status: `down`.';
    }
}
