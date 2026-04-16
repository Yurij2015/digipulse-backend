<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
                'message' => 'Telegram is already connected.'
            ]);
        }

        // Generate a new secure token
        $token = Str::random(32);

        $user->update([
            'telegram_connection_token' => $token
        ]);

        $botUsername = config('services.telegram.bot_username', 'DigiPulseBot');

        return response()->json([
            'connected' => false,
            'token' => $token,
            'bot_username' => $botUsername,
            'url' => "https://t.me/{$botUsername}?start={$token}"
        ]);
    }

    /**
     * Handle incoming webhooks from Telegram.
     * @throws ConnectionException
     */
    public function webhook(Request $request): JsonResponse
    {
        // 1. Get the message info
        $message = $request->input('message');

        if (!$message) {
            return response()->json(['status' => 'ignored']);
        }

        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'] ?? null;

        if (!$text || !$chatId) {
            return response()->json(['status' => 'ignored']);
        }

        // 2. Check if it's a deep link start command
        if (str_starts_with($text, '/start ')) {
            $token = str_replace('/start ', '', $text);
            $token = trim($token);

            // 3. Find the user by token
            $user = User::where('telegram_connection_token', $token)->first();

            if ($user) {
                // 4. Update user record
                $user->update([
                    'telegram_chat_id' => $chatId,
                    'telegram_connection_token' => null,
                ]);

                // 5. Send a welcome message back to the user via Telegram API
                $this->sendMessage($chatId);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Send a simple text message via Telegram API.
     * @throws ConnectionException
     */
    private function sendMessage($chatId): void
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => "✅ **Success!**\n\nYou have connected Telegram to your DigiPulse account. You will now receive notifications here if your sites go offline.",
            'parse_mode' => 'Markdown',
        ]);
    }
}
