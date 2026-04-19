<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Added Log facade
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class TelegramController extends Controller
{
    #[OA\Get(
        path: '/api/telegram/connect',
        summary: 'Get Telegram connection link',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Telegram'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'connected', type: 'boolean', example: false),
                        new OA\Property(property: 'token', type: 'string', example: 'random_32_char_token', nullable: true),
                        new OA\Property(property: 'bot_username', type: 'string', example: 'DigiPulseBot', nullable: true),
                        new OA\Property(property: 'url', type: 'string', example: 'https://t.me/DigiPulseBot?start=random_32_char_token', nullable: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Telegram is already connected.', nullable: true),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Generate a connection token for the authenticated user.
     */
    public function connect(Request $request): JsonResponse
    {
        $user = $request->user();

        // If already connected
        if ($user->telegram_chat_id) {
            return response()->json([
                'connected' => true,
                'message' => 'Telegram is already connected.',
            ]);
        }

        // Generate a new secure token
        $token = Str::random(32);

        $user->update([
            'telegram_connection_token' => $token,
        ]);

        $botUsername = config('services.telegram.bot_username', 'DigiPulseBot');

        return response()->json([
            'connected' => false,
            'token' => $token,
            'bot_username' => $botUsername,
            'url' => "https://t.me/{$botUsername}?start={$token}",
        ]);
    }

    #[OA\Post(
        path: '/api/telegram/disconnect',
        summary: 'Disconnect Telegram bot',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Telegram'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Disconnect the Telegram bot for the authenticated user.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();
        $chatId = $user->telegram_chat_id;

        if ($chatId) {
            try {
                // Send a goodbye message before disconnecting
                $this->sendSimpleMessage($chatId, "🔌 **Disconnected**\n\nYou have successfully disconnected Telegram notifications for DigiPulse. You will no longer receive alerts here.");
                Log::info('Telegram Disconnect: Goodbye message sent.', ['user_id' => $user->id, 'chat_id' => $chatId]);
            } catch (\Exception $e) {
                Log::warning('Telegram Disconnect: Failed to send goodbye message.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        $user->update([
            'telegram_chat_id' => null,
            'telegram_connection_token' => null,
        ]);

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Handle incoming webhooks from Telegram.
     *
     * @throws ConnectionException
     */
    public function webhook(Request $request): JsonResponse
    {
        Log::info('Telegram Webhook: Incoming request', ['payload' => $request->all()]); // Log incoming request

        $message = $request->input('message');

        if (! $message) {
            Log::info('Telegram Webhook: No message found in request, ignoring.', ['payload' => $request->all()]); // Log ignored message

            return response()->json(['status' => 'ignored']);
        }

        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'] ?? null;

        if (! $text || ! $chatId) {
            Log::info('Telegram Webhook: Message missing text or chat ID, ignoring.', ['message' => $message]); // Log ignored message

            return response()->json(['status' => 'ignored']);
        }

        if (str_starts_with($text, '/start')) {
            $token = trim(str_replace('/start', '', $text));

            if (empty($token)) {
                Log::info('Telegram Webhook: Generic start command detected (no token).', ['chat_id' => $chatId]);
                $this->sendSimpleMessage($chatId, "👋 **Welcome to DigiPulse!**\n\nTo connect your account and receive downtime notifications, please use the unique link from your **Settings** page in the DigiPulse dashboard.");

                return response()->json(['status' => 'ok']);
            }

            Log::info('Telegram Webhook: Deep link start command detected.', ['token' => $token, 'chat_id' => $chatId]);

            $user = User::where('telegram_connection_token', $token)->first();

            if ($user) {
                Log::info('Telegram Webhook: User found for token.', ['user_id' => $user->id, 'chat_id' => $chatId]);
                $user->update([
                    'telegram_chat_id' => $chatId,
                    'telegram_connection_token' => null,
                ]);
                Log::info('Telegram Webhook: User record updated.', ['user_id' => $user->id, 'chat_id' => $chatId]);

                $this->sendSuccessMessage($chatId);
                Log::info('Telegram Webhook: Welcome message sent.', ['user_id' => $user->id, 'chat_id' => $chatId]);
            } else {
                Log::warning('Telegram Webhook: No user found for token.', ['token' => $token, 'chat_id' => $chatId]);
                $this->sendSimpleMessage($chatId, "⚠️ **Connection Failed**\n\nThe link you used is either invalid or has expired. Please generate a new connection link in your Settings.");
            }
        } else {
            Log::info('Telegram Webhook: Received message is not a start command.', ['text' => $text, 'chat_id' => $chatId]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Send a success connection message.
     */
    private function sendSuccessMessage($chatId): void
    {
        $this->sendSimpleMessage($chatId, "✅ **Success!**\n\nYou have connected Telegram to your DigiPulse account. You will now receive notifications here if your sites go offline.");
    }

    /**
     * Send a simple text message via Telegram API.
     *
     * @throws ConnectionException
     */
    private function sendSimpleMessage($chatId, string $text): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            Log::warning('Telegram Webhook: Telegram bot token not configured, cannot send message.', ['chat_id' => $chatId]); // Log missing token

            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
            Log::info('Telegram Webhook: Message sent successfully via Telegram API.', ['chat_id' => $chatId]); // Log successful send
        } catch (ConnectionException $e) {
            Log::error('Telegram Webhook: Failed to send message via Telegram API.', ['chat_id' => $chatId, 'error' => $e->getMessage()]); // Log send error
            throw $e; // Re-throw the exception as it's part of the method signature
        }
    }
}
