<?php

namespace App\Http\Controllers\Api\V1\Mcp;

use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\CheckResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class IncidentsController extends Controller
{
    public function __construct(
        private readonly SiteManagementRepositoryInterface $siteRepository,
    ) {}

    #[OA\Get(
        path: '/api/v1/mcp/incidents',
        operationId: 'mcpIncidents',
        description: 'Returns recent "down" check results for the user\'s sites. Intended for MCP tool use.',
        summary: 'MCP: recent downtime incidents',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['MCP'],
        parameters: [
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'site_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 200, default: 50)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 0, default: 0)),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Incidents list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'incidents', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'total', type: 'integer'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer'],
            'site_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;
        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        $siteIds = $this->siteRepository->findIdsByUser(
            $userId,
            $validated['project_id'] ?? null,
            $validated['site_id'] ?? null,
        );

        $query = CheckResult::whereIn('site_id', $siteIds)
            ->where('status', 'down')
            ->with('site:id,name,url,project_id')
            ->when(isset($validated['from']), fn ($q) => $q->where('checked_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($q) => $q->where('checked_at', '<=', $validated['to']))
            ->orderBy('checked_at', 'desc')
            ->limit($limit)
            ->offset($offset);

        $incidents = $query->get()->map(fn ($r) => [
            'site_id' => $r->site_id,
            'site_name' => $r->site?->name,
            'site_url' => $r->site?->url,
            'project_id' => $r->site?->project_id,
            'checked_at' => $r->checked_at->toIso8601String(),
            'response_time_ms' => $r->response_time_ms,
            'error' => $r->error_message,
        ]);

        return response()->json([
            'data' => [
                'incidents' => $incidents,
                'total' => $incidents->count(),
            ],
        ]);
    }
}
