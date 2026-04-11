<?php

namespace App\Docs\Schemas\Sites;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'HistoryIncident',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', nullable: true, example: 123),
        new OA\Property(property: 'configuration_id', type: 'integer', example: 5),
        new OA\Property(property: 'status', type: 'string', example: 'down'),
        new OA\Property(property: 'response_time_ms', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'error_message', type: 'string', nullable: true, example: 'Connection timeout'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true),
        new OA\Property(property: 'checked_at', type: 'string', format: 'date-time', example: '2024-04-07T10:15:22'),
    ]
)]
class HistoryIncident {}
