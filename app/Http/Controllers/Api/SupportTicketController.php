<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SupportTickets\ReplySupportTicketRequest;
use App\Http\Requests\Api\SupportTickets\StoreSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\NewSupportTicketNotification;
use App\Notifications\SupportTicketReplyNotification;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class SupportTicketController extends Controller
{
    #[OA\Get(
        path: '/api/support/tickets',
        summary: 'List user support tickets',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportTicketSchema')
                )
            ),
        ]
    )]
    /**
     * List user's support tickets.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tickets = $request->user()->supportTickets()->latest('updated_at')->get();

        return SupportTicketResource::collection($tickets);
    }

    #[OA\Get(
        path: '/api/support/tickets/{ticket}',
        summary: 'Get specific support ticket',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'The ticket ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/SupportTicketSchema')
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized'
            ),
        ]
    )]
    /**
     * Display a specific support ticket with its messages.
     */
    public function show(SupportTicket $ticket, Request $request): SupportTicketResource|JsonResponse
    {
        // Only owner or admin can see the ticket
        if ($ticket->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->load(['messages.user']);

        return new SupportTicketResource($ticket);
    }

    #[OA\Post(
        path: '/api/support/tickets',
        summary: 'Create a new support ticket',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['subject', 'message'],
                properties: [
                    new OA\Property(property: 'subject', type: 'string', example: 'Issue with payment'),
                    new OA\Property(property: 'message', type: 'string', example: 'I was charged twice.'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'medium'),
                    new OA\Property(property: 'contact_email', type: 'string', format: 'email', example: 'user@example.com'),
                ]
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Ticket created successfully'
            ),
        ]
    )]
    /**
     * Store a newly created support ticket.
     */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ticket = SupportTicket::create([
            'user_id' => $request->user()?->id,
            'contact_email' => $request->user() ? $request->user()->email : ($validated['contact_email'] ?? null),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);
        Log::info('Support ticket created', ['ticket_id' => $ticket->id, 'user_id' => $ticket->user_id]);

        // Notify Admin
        $adminEmail = config('app.admin_email');
        $admin = User::where('email', $adminEmail)->first();

        if ($admin) {
            Log::info('Notifying admin about new ticket', ['admin_id' => $admin->id, 'email' => $adminEmail]);
            $admin->notify(new NewSupportTicketNotification($ticket));

            // Filament Notification for Admin
            Notification::make()
                ->title('New Support Ticket')
                ->body("Subject: {$ticket->subject}")
                ->icon('heroicon-o-ticket')
                ->warning()
                ->sendToDatabase($admin)
                ->broadcast($admin);
        } else {
            Log::warning('Admin user not found for notification', ['email' => $adminEmail]);
        }

        return response()->json([
            'message' => 'Ticket submitted successfully.',
            'ticket' => $ticket,
        ], 201);
    }

    #[OA\Post(
        path: '/api/support/tickets/{ticket}/reply',
        summary: 'Add a message to an existing ticket',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['message'],
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Thank you for the help!'),
                ]
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'The ticket ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Reply sent successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SupportTicketMessageSchema')
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized'
            ),
        ]
    )]
    /**
     * Add a message to an existing ticket.
     */
    public function reply(SupportTicket $ticket, ReplySupportTicketRequest $request): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_admin_reply' => false,
        ]);

        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        }

        $adminEmail = config('app.admin_email');
        $admin = User::where('email', $adminEmail)->first();
        $admin?->notify(new SupportTicketReplyNotification($message));

        // Filament Notification for Admin
        if ($admin) {
            Notification::make()
                ->title('New Support Reply')
                ->body("From {$message->user->name}: ".substr($message->message, 0, 50).'...')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->success()
                ->sendToDatabase($admin)
                ->broadcast($admin);
        }

        // Broadcast Event for WebSockets
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Reply sent successfully.',
            'data' => $message,
        ], 201);
    }
}
