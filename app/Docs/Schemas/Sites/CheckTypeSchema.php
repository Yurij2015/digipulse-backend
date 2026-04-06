<?php

namespace App\Docs\Schemas\Sites;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CheckTypeSchema',
    type: 'object',
    required: ['id', 'name', 'slug', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'HTTP Status'),
        new OA\Property(property: 'slug', type: 'string', example: 'http'),
        new OA\Property(property: 'description', type: 'string', example: 'Verifies the website returns a 200 OK.', nullable: true),
        new OA\Property(property: 'icon', type: 'string', example: 'heroicon-o-globe-alt', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ]
)]
class CheckTypeSchema {}
