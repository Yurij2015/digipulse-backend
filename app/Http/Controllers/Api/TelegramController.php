<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Added Log facade
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

    /**
     * Handle incoming webhooks from Telegram.
     *
     * @throws ConnectionException
     */
    public function webhook(Request $request): JsonResponse
    {
        Log::info('Telegram Webhook: Incoming request', ['payload' => $request->all()]); // Log incoming request

        // 1. Get the message info
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

        // 2. Check if it's a deep link start command
        if (str_starts_with($text, '/start ')) {
            $token = str_replace('/start ', '', $text);
            $token = trim($token);
            Log::info('Telegram Webhook: Deep link start command detected.', ['token' => $token, 'chat_id' => $chatId]); // Log deep link detection

            // 3. Find the user by token
            $user = User::where('telegram_connection_token', $token)->first();

            if ($user) {
                Log::info('Telegram Webhook: User found for token.', ['user_id' => $user->id, 'chat_id' => $chatId]); // Log user found
                // 4. Update user record
                $user->update([
                    'telegram_chat_id' => $chatId,
                    'telegram_connection_token' => null,
                ]);
                Log::info('Telegram Webhook: User record updated.', ['user_id' => $user->id, 'chat_id' => $chatId]); // Log user update

                // 5. Send a welcome message back to the user via Telegram API
                $this->sendMessage($chatId);
                Log::info('Telegram Webhook: Welcome message sent.', ['user_id' => $user->id, 'chat_id' => $chatId]); // Log message sent
            } else {
                Log::warning('Telegram Webhook: No user found for token.', ['token' => $token, 'chat_id' => $chatId]); // Log no user found
            }
        } else {
            Log::info('Telegram Webhook: Received message is not a deep link start command.', ['text' => $text, 'chat_id' => $chatId]); // Log non-start command
        }


        return response()->json(['status' => 'ok']);
    }

    /**
     * Send a simple text message via Telegram API.
     *
     * @throws ConnectionException
     */
    private function sendMessage($chatId): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            Log::warning('Telegram Webhook: Telegram bot token not configured, cannot send message.', ['chat_id' => $chatId]); // Log missing token
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "✅ **Success!**\n\nYou have connected Telegram to your DigiPulse account. You will now receive notifications here if your sites go offline.",
                'parse_mode' => 'Markdown',
            ]);
            Log::info('Telegram Webhook: Message sent successfully via Telegram API.', ['chat_id' => $chatId]); // Log successful send
        } catch (ConnectionException $e) {
            Log::error('Telegram Webhook: Failed to send message via Telegram API.', ['chat_id' => $chatId, 'error' => $e->getMessage()]); // Log send error
            throw $e; // Re-throw the exception as it's part of the method signature
        }
    }
}
