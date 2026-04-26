<?php

namespace App\Channels;

use App\Models\User;
use App\Notifications\Contracts\TelegramNotification;
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
        if (! $notification instanceof TelegramNotification) {
            throw new \RuntimeException('Notification must implement TelegramNotification interface to be sent via TelegramChannel.');
        }
        /** @var User $notifiable */
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);

        if (! $chatId) {
            Log::warning('Telegram chat ID not found for notifiable', [
                'notifiable_id' => $notifiable->id ?? 'unknown',
                'notifiable_class' => get_class($notifiable),
            ]);

            return;
        }

        $data = $notification->toTelegram($notifiable);
        Log::info('Sending Telegram notification', [
            'chat_id' => $chatId,
            'notification' => get_class($notification),
        ]);
        $this->sendMessage($chatId, $data);
    }

    /**
     * Send a message to the support team (currently the administrator).
     *
     * @throws \JsonException
     */
    public function sendToSupport(string|array $data): void
    {
        $adminEmail = config('app.admin_email');
        $admin = User::where('email', $adminEmail)->first();

        if ($admin && $admin->telegram_chat_id) {
            $this->sendMessage($admin->telegram_chat_id, $data);
        } else {
            Log::warning('Telegram sendToSupport failed: Admin user or telegram_chat_id not found.', ['admin_email' => $adminEmail]);
        }
    }

    /**
     * Send a manual message to a specific chat.
     *
     * @throws \JsonException
     */
    public function sendMessage(string|int $chatId, string|array $data): void
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            Log::warning('Telegram Notification failed: TELEGRAM_BOT_TOKEN is missing in config/services.php');

            return;
        }

        Log::info('sendMessage payload data', ['chat_id' => $chatId, 'data' => $data]);

        $payload = [
            'chat_id' => $chatId,
            'parse_mode' => 'MarkdownV2',
        ];

        if (is_array($data)) {
            $payload['text'] = $data['text'];
            if (isset($data['reply_markup'])) {
                $payload['reply_markup'] = json_encode($data['reply_markup'], JSON_THROW_ON_ERROR);
            }
            if (isset($data['parse_mode'])) {
                $payload['parse_mode'] = $data['parse_mode'];
            }
        } else {
            $payload['text'] = $data;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            if ($response->successful()) {
                Log::info('Telegram notification sent successfully', ['chat_id' => $chatId]);
            } else {
                Log::error('Telegram notification failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'chat_id' => $chatId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification: '.$e->getMessage());
        }
    }

    /**
     * Answer a callback query from an inline keyboard button.
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to answer Telegram callback query: '.$e->getMessage());
        }
    }
}
