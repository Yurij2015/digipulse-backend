<?php

namespace App\Http\Controllers\Api\V1\Mcp;

use App\Domain\Monitoring\Contracts\ProjectRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Models\Project as DomainProject;
use App\Domain\Monitoring\Models\Site as DomainSite;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OverviewController extends Controller
{
    public function __construct(
        private readonly SiteManagementRepositoryInterface $siteRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    #[OA\Get(
        path: '/api/v1/mcp/overview',
        operationId: 'mcpOverview',
        description: 'Returns all user sites grouped by project with status summary. Intended for MCP tool use.',
        summary: 'MCP: sites and projects overview',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['MCP'],
        parameters: [
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Overview data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'projects', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'sites_without_project', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(
                                    property: 'summary',
                                    properties: [
                                        new OA\Property(property: 'total_sites', type: 'integer'),
                                        new OA\Property(property: 'up', type: 'integer'),
                                        new OA\Property(property: 'down', type: 'integer'),
                                        new OA\Property(property: 'pending', type: 'integer'),
                                        new OA\Property(property: 'avg_uptime', type: 'number'),
                                        new OA\Property(property: 'avg_response_time', type: 'number'),
                                    ],
                                    type: 'object'
                                ),
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
        $userId = $request->user()->id;
        $projectId = $request->integer('project_id') ?: null;

        $sites = $this->siteRepository->findByUser($userId, $projectId);
        $projects = $this->projectRepository->findByUser($userId);

        $sitesFormatted = array_map(fn (DomainSite $site) => $this->formatSite($site), $sites);
        $projectsById = collect($projects)->keyBy('id');
        $grouped = collect($sitesFormatted)->groupBy(fn ($s) => $s['project_id'] ?? 'none');

        $projectsOut = $projectsById->map(function (DomainProject $project) use ($grouped) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'sites' => $grouped->get($project->id, collect())->values(),
            ];
        })->values();

        $sitesCollection = collect($sitesFormatted);

        return response()->json([
            'data' => [
                'projects' => $projectsOut,
                'sites_without_project' => $grouped->get('none', collect())->values(),
                'summary' => [
                    'total_sites' => count($sites),
                    'up' => $sitesCollection->where('status', 'up')->count(),
                    'down' => $sitesCollection->where('status', 'down')->count(),
                    'pending' => $sitesCollection->where('status', 'pending')->count(),
                    'avg_uptime' => round($sitesCollection->whereNotNull('uptime')->avg('uptime') ?? 0, 2),
                    'avg_response_time' => round($sitesCollection->whereNotNull('response_time')->avg('response_time') ?? 0, 0),
                ],
            ],
        ]);
    }

    private function formatSite(DomainSite $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'url' => $site->url,
            'status' => $site->status,
            'uptime' => $site->uptime,
            'response_time' => $site->responseTime,
            'last_checked_at' => $site->lastCheckedAt,
            'project_id' => $site->projectId,
            'ssl_valid' => isset($site->sslInfo['days_remaining']) ? $site->sslInfo['days_remaining'] > 0 : null,
            'ssl_expires_at' => $site->sslInfo['expires_at'] ?? null,
            'ssl_days_remaining' => $site->sslInfo['days_remaining'] ?? null,
        ];
    }
}
