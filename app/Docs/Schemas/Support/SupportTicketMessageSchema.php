<?php

namespace App\Docs\Schemas\Support;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SupportTicketMessageSchema',
    title: 'Support Ticket Message',
    description: 'A message within a support ticket',
    required: ['id', 'message', 'is_admin_reply']
)]
class SupportTicketMessageSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'message', type: 'string', example: 'Thank you for your request. We are looking into it.')]
    public string $message;

    #[OA\Property(property: 'is_admin_reply', type: 'boolean', example: false)]
    public bool $is_admin_reply;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    public string $created_at;

    #[OA\Property(property: 'user_name', type: 'string', example: 'John Doe')]
    public string $user_name;
}
