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

        if ($notifiable->notify_telegram ?? false) {
            $channels[] = TelegramChannel::class;
        }

        if ($notifiable->notify_email ?? false) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->daysRemaining < 0) {
            $expiredDaysAgo = abs($this->daysRemaining);

            return (new MailMessage)
                ->subject("🚨 SSL Certificate Expired: {$this->site->name}")
                ->greeting('Urgent!')
                ->line("The SSL certificate for your site **{$this->site->name}** ({$this->site->url}) has already **expired**.")
                ->line("It expired **{$expiredDaysAgo} days ago**.")
                ->action('View Site Dashboard', rtrim(config('app.frontend_url', config('app.url')), '/').'/dashboard')
                ->line('Renew it immediately to restore secure HTTPS connections.');
        }

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

        if ($this->daysRemaining < 0) {
            $expiredDaysAgo = abs($this->daysRemaining);

            return "🚨 <b>SSL Certificate Expired!</b>\n\n".
                   "The SSL certificate for <b>{$safeName}</b> ({$safeUrl}) expired <code>{$expiredDaysAgo}</code> days ago.\n\n".
                   'Renew it immediately to restore secure connections.';
        }

        return "⚠️ <b>SSL Expiration Warning!</b>\n\n".
               "The SSL certificate for <b>{$safeName}</b> ({$safeUrl}) expires in <code>{$this->daysRemaining}</code> days.\n\n".
               'Please renew it soon to keep your site secure.';
    }
}
