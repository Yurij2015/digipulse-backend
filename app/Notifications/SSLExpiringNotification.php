<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\Site;
use App\Notifications\Contracts\TelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SSLExpiringNotification extends Notification implements ShouldQueue, TelegramNotification
{
    use Queueable;

    public function __construct(
        private readonly Site $site,
        private readonly int $daysRemaining
    ) {}

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
            ->subject("⚠️ SSL Certificate Expiration: {$this->site->name}")
            ->greeting('Hello!')
            ->line("The SSL certificate for your site **{$this->site->name}** ({$this->site->url}) is about to expire.")
            ->line("Days remaining: **{$this->daysRemaining}**.")
            ->action('View Site Dashboard', rtrim(config('app.frontend_url', config('app.url')), '/').'/dashboard')
            ->line('Please renew your certificate as soon as possible to avoid downtime.');
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(mixed $notifiable): string
    {
        $safeName = htmlspecialchars($this->site->name ?? '');
        $safeUrl = htmlspecialchars($this->site->url ?? '');

        return "⚠️ <b>SSL Expiration Warning!</b>\n\n".
               "The SSL certificate for <b>{$safeName}</b> ({$safeUrl}) expires in <code>{$this->daysRemaining}</code> days.\n\n".
               'Please renew it soon to keep your site secure.';
    }
}
