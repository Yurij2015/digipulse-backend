<?php

namespace App\Docs\Schemas\Sites;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConfigurationSchema',
    type: 'object',
    required: ['id', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'check_type', ref: '#/components/schemas/CheckTypeSchema'),
        new OA\Property(property: 'params', type: 'object', example: ['keyword' => 'test'], nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'last_status', type: 'string', example: 'up', nullable: true),
        new OA\Property(property: 'last_checked_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class ConfigurationSchema {}
