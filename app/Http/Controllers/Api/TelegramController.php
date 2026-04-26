<?php

namespace App\Http\Controllers\Api;

use App\Channels\TelegramChannel;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class TelegramController extends Controller
{
    public function __construct(
        protected TelegramChannel $telegram
    ) {}

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
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserSchema'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Disconnect the Telegram bot for the authenticated user.
     *
     * @throws \JsonException
     */
    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();
        $chatId = $user->telegram_chat_id;

        if ($chatId) {
            $this->telegram->sendMessage($chatId, [
                'text' => "🔌 *Disconnected*\n\nYou have successfully disconnected Telegram notifications for DigiPulse\. You will no longer receive alerts here\.",
                'parse_mode' => 'MarkdownV2',
            ]);
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
     * @throws \JsonException
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Telegram webhook payload', ['payload' => $payload]);

        if (isset($payload['callback_query'])) {
            return $this->processTelegramCallback($payload['callback_query']);
        }

        $message = $payload['message'] ?? null;

        Log::info('Telegram webhook message', ['message' => $message]);

        if (! $message) {
            return response()->json(['status' => 'ok']);
        }

        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'] ?? null;

        if (! $text || ! $chatId) {
            return response()->json(['status' => 'ok']);
        }

        if (Cache::has("telegram_reply_ticket_{$chatId}")) {
            return $this->handleSupportReply($chatId, $text);
        }

        if (str_starts_with($text, '/start')) {
            $token = trim(str_replace('/start', '', $text));

            if (empty($token)) {
                $this->telegram->sendMessage($chatId, [
                    'text' => "👋 *Welcome to DigiPulse\!*\n\nTo connect your account and receive downtime notifications, please use the unique link from your *Settings* page in the DigiPulse dashboard\.",
                    'parse_mode' => 'MarkdownV2',
                ]);

                return response()->json(['status' => 'ok']);
            }

            $user = User::where('telegram_connection_token', $token)->first();

            if ($user) {
                $user->update([
                    'telegram_chat_id' => $chatId,
                    'telegram_connection_token' => null,
                ]);

                $this->telegram->sendMessage($chatId, [
                    'text' => "✅ *Success\!*\n\nYou have connected Telegram to your DigiPulse account\. You will now receive notifications here if your sites go offline\.",
                    'parse_mode' => 'MarkdownV2',
                ]);
            } else {
                $this->telegram->sendMessage($chatId, [
                    'text' => "⚠️ *Connection Failed*\n\nThe link you used is either invalid or has expired\. Please generate a new connection link in your Settings\.",
                    'parse_mode' => 'MarkdownV2',
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle button clicks (callback queries).
     *
     * @throws \JsonException
     */
    private function processTelegramCallback(array $callbackQuery): JsonResponse
    {
        Log::info('processTelegramCallback payload data', ['callbackQuery' => $callbackQuery]);

        $chatId = $callbackQuery['from']['id'];
        $data = $callbackQuery['data'];
        $callbackQueryId = $callbackQuery['id'];

        $adminEmail = config('app.admin_email');
        $admin = User::where('email', $adminEmail)->first();

        if (! $admin || $admin->telegram_chat_id !== $chatId) {
            $this->telegram->answerCallbackQuery($callbackQueryId, '⚠️ You are not authorized to reply.');

            return response()->json(['status' => 'forbidden']);
        }

        if (str_starts_with($data, 'support_reply:')) {
            $ticketId = str_replace('support_reply:', '', $data);
            Cache::put("telegram_reply_ticket_{$chatId}", $ticketId, now()->addMinutes(15));

            $this->telegram->sendMessage($chatId, [
                'text' => "✍️ *Ticket \#{$ticketId}*\n\nPlease enter your answer below\. I will send it to the user\.",
                'parse_mode' => 'MarkdownV2',
            ]);
            $this->telegram->answerCallbackQuery($callbackQueryId);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle the actual text reply from the admin.
     *
     * @throws \JsonException
     */
    private function handleSupportReply(int $chatId, string $text): JsonResponse
    {
        $ticketId = Cache::pull("telegram_reply_ticket_{$chatId}");
        $ticket = SupportTicket::find($ticketId);

        if (! $ticket) {
            $this->telegram->sendMessage($chatId, "⚠️ Error: Ticket #{$ticketId} not found.");

            return response()->json(['status' => 'ok']);
        }

        $adminEmail = config('app.admin_email');
        $admin = User::where('email', $adminEmail)->first();

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $admin?->id,
            'message' => $text,
            'is_admin_reply' => true,
        ]);

        $ticket->update(['status' => 'in_progress']);

        $this->telegram->sendMessage($chatId, [
            'text' => "✅ *Response sent\!*\n\nYour message has been delivered and saved to Ticket \#{$ticket->id}\.",
            'parse_mode' => 'MarkdownV2',
        ]);

        return response()->json(['status' => 'ok']);
    }
}
