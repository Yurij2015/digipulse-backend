<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Notifications\Contracts\TelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonitorHeartbeatMissingNotification extends Notification implements ShouldQueue, TelegramNotification
{
    use Queueable;

    public function __construct(private readonly int $minutesSinceLastBeat) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_telegram ?? false) {
            $channels[] = TelegramChannel::class;
        }

        if ($notifiable->notify_email ?? false) {
            $channels[] = 'mail';
        }

        return $channels ?: ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('🔴 Go monitor is not responding')
            ->greeting('System alert')
            ->line("The Go monitor service has not sent a heartbeat for **{$this->minutesSinceLastBeat} minutes**.")
            ->line('Site checks may not be running. Please investigate immediately.');
    }

    public function toTelegram(mixed $notifiable): string
    {
        return "🔴 <b>Go monitor is not responding</b>\n\n".
               "No heartbeat received for <b>{$this->minutesSinceLastBeat} min</b>.\n".
               'Site checks may not be running.';
    }
}
