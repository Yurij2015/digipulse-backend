<?php

namespace App\Http\Controllers\Api\V1\Mcp;

use App\Domain\Monitoring\Contracts\ProjectRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Models\Project as DomainProject;
use App\Domain\Monitoring\Models\Site as DomainSite;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function __construct(
        private readonly SiteManagementRepositoryInterface $siteRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

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
