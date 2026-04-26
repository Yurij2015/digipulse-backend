<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Controller;
use App\Models\CheckResult;
use App\Models\SiteCheckConfiguration;
use App\Notifications\SiteDownNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class InternalCheckResultController extends Controller
{
    #[OA\Post(
        path: '/api/webhooks/results',
        summary: 'Store a new check result from the monitor service',
        security: [['frontendKey' => []]],
        tags: ['Internal'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['configuration_id', 'status'],
                properties: [
                    new OA\Property(property: 'configuration_id', type: 'integer', example: 1),
                    new OA\Property(property: 'status', type: 'string', enum: ['up', 'down', 'slow'], example: 'up'),
                    new OA\Property(property: 'response_time_ms', type: 'integer', example: 150),
                    new OA\Property(property: 'error_message', type: 'string', example: 'Connection timeout'),
                    new OA\Property(property: 'metadata', type: 'object', example: ['ip' => '1.2.3.4']),
                ]
            )
        ),
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'configuration_id' => ['required', 'exists:site_check_configurations,id'],
            'status' => ['required', 'string', 'in:up,down,slow'],
            'response_time_ms' => ['nullable', 'integer'],
            'error_message' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $config = SiteCheckConfiguration::with('site')->findOrFail($validated['configuration_id']);

        $previousStatus = $config->last_status;

        DB::transaction(static function () use ($config, $validated) {
            $config->update([
                'last_status' => $validated['status'],
                'last_checked_at' => now(),
            ]);

            CheckResult::create([
                'site_id' => $config->site_id,
                'configuration_id' => $config->id,
                'status' => $validated['status'],
                'response_time_ms' => $validated['response_time_ms'] ?? null,
                'error_message' => $validated['error_message'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
                'checked_at' => now(),
            ]);
        });

        if ($previousStatus !== 'down' && $validated['status'] === 'down' && $config->site->user) {
            $config->site->user->notify(new SiteDownNotification($config->site));
        }

        SiteController::clearUserSitesCache($config->site->user_id);

        return response()->json(['success' => true]);
    }
}
