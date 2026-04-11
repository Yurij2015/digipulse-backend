<?php

namespace App\Docs\Schemas\Sites;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SiteHistorySchema',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'stats',
            type: 'array',
            description: 'Hourly aggregated check data for graphing',
            items: new OA\Items(ref: '#/components/schemas/HistoryStatBucket')
        ),
        new OA\Property(
            property: 'incidents',
            type: 'array',
            description: 'Individual "down" events for incident markers on the chart',
            items: new OA\Items(ref: '#/components/schemas/HistoryIncident')
        ),
    ]
)]
class SiteHistorySchema {}
