<?php

namespace App\Http\Controllers\Api\Internal;

use App\Domain\Monitoring\DTOs\MonitoringResultDTO;
use App\Domain\Monitoring\UseCases\ProcessMonitoringResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Internal\StoreMonitoringResultRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class InternalCheckResultController extends Controller
{
    #[OA\Post(
        path: '/api/webhooks/results',
        summary: 'Store a new check result from the monitor service',
        security: [['frontendKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['configuration_id', 'status'],
                properties: [
                    new OA\Property(property: 'configuration_id', type: 'integer', example: 1),
                    new OA\Property(property: 'status', type: 'string', example: 'up', enum: ['up', 'down', 'slow']),
                    new OA\Property(property: 'response_time_ms', type: 'integer', example: 150),
                    new OA\Property(property: 'error_message', type: 'string', example: 'Connection timeout'),
                    new OA\Property(property: 'metadata', type: 'object', example: ['ip' => '1.2.3.4']),
                ]
            )
        ),
        tags: ['Internal'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Result stored successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                    ]
                )
            ),
        ]
    )]
    /**
     * Store a new check result from the monitor service.
     *
     * @throws \Throwable
     */
    public function store(StoreMonitoringResultRequest $request, ProcessMonitoringResult $useCase): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(static fn () => $useCase->execute(
            MonitoringResultDTO::fromArray($validated)
        ));

        return response()->json(['success' => true]);
    }
}
