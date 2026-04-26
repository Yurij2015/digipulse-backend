<?php

namespace App\Docs\Schemas\Support;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SupportTicketSchema',
    title: 'Support Ticket',
    description: 'A support ticket record',
    required: ['id', 'subject', 'message', 'status', 'priority']
)]
class SupportTicketSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'subject', type: 'string', example: 'Help with site setup')]
    public string $subject;

    #[OA\Property(property: 'message', type: 'string', example: 'I am having trouble adding my first site.')]
    public string $message;

    #[OA\Property(property: 'status', type: 'string', enum: ['open', 'closed', 'pending'], example: 'open')]
    public string $status;

    #[OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'medium')]
    public string $priority;

    #[OA\Property(property: 'contact_email', type: 'string', format: 'email', example: 'user@example.com')]
    public string $contact_email;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    public string $created_at;

    #[OA\Property(property: 'updated_at', type: 'string', format: 'date-time')]
    public string $updated_at;

    #[OA\Property(
        property: 'messages',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/SupportTicketMessageSchema')
    )]
    public array $messages;
}
