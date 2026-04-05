<?php

namespace App\Docs\Schemas\User;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserSchema',
    type: 'object',
    required: ['id', 'name', 'email', 'first_name', 'last_name', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'johndoug'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'johtdouj@gmail.pro'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T09:01:11.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T09:01:11.000000Z'),
    ]
)]
class UserSchema {}
