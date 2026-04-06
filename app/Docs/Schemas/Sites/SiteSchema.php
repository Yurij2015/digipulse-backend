<?php

namespace App\Docs\Schemas\Sites;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SiteSchema',
    type: 'object',
    required: ['id', 'name', 'url', 'update_interval', 'is_active', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'name', type: 'string', example: 'Example.com'),
        new OA\Property(property: 'url', type: 'string', example: 'https://example.com'),
        new OA\Property(property: 'update_interval', type: 'integer', example: 300),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'configurations',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ConfigurationSchema')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T09:01:11.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T09:01:11.000000Z'),
    ]
)]
class SiteSchema {}
