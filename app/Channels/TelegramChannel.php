<?php

namespace App\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        /** @var User $notifiable */
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);

        if (! $chatId) {
            return;
        }

        $message = $notification->toTelegram($notifiable);
        $token = config('services.telegram.bot_token');

        if (! $token) {
            Log::warning('Telegram Notification failed: TELEGRAM_BOT_TOKEN is missing in config/services.php');
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'MarkdownV2',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification: ' . $e->getMessage());
        }
    }
}
