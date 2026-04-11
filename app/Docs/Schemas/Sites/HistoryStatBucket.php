<?php

namespace App\Docs\Schemas\Sites;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'HistoryStatBucket',
    type: 'object',
    properties: [
        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2024-04-07T10:00:00+00:00'),
        new OA\Property(property: 'avg_response_time', type: 'number', format: 'float', example: 142.5),
        new OA\Property(property: 'uptime_percentage', type: 'number', format: 'float', example: 98.33),
        new OA\Property(property: 'count', type: 'integer', example: 60),
    ]
)]
class HistoryStatBucket {}
