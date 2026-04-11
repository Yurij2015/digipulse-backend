<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteHistoryResource;
use App\Models\CheckResult;
use App\Models\CheckResultArchive;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SiteHistoryController extends Controller
{
    #[OA\Get(
        path: '/api/sites/{site}/history',
        summary: 'Get aggregated check history for a site',
        description: 'Returns hourly aggregated stats (avg response time, uptime %) and a list of individual "down" incidents for the given ISO week. Defaults to the current week if no `week` param is provided. For past weeks (older than 7 days), data is served from the weekly archive.',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Sites'],
        parameters: [
            new OA\Parameter(
                name: 'site',
                in: 'path',
                description: 'The site ID',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'week',
                in: 'query',
                description: 'ISO-8601 week string (e.g. 2024-W15). Defaults to the current week.',
                required: false,
                schema: new OA\Schema(type: 'string', example: '2024-W15')
            ),
            new OA\Parameter(
                name: 'configuration_id',
                in: 'query',
                description: 'Filter results to a single check configuration.',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 3)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aggregated history returned',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/SiteHistorySchema'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Site not found'),
            new OA\Response(response: 422, description: 'Validation error (invalid week format)'),
        ]
    )]
    /**
     * Get aggregated check history for a site by week.
     */
    public function index(Request $request, Site $site): SiteHistoryResource
    {
        $validated = $request->validate([
            'week' => ['nullable', 'string', 'regex:/^\d{4}-W\d{2}$/'],
            'configuration_id' => ['nullable', 'exists:site_check_configurations,id'],
        ]);

        $weekStr = $validated['week'] ?? now()->format('Y-\WW');

        [$year, $weekPart] = explode('-W', $weekStr);
        $year = (int)$year;
        $week = (int)$weekPart;

        // Determine if it's the current ISO week
        $isCurrentWeek = $weekStr === now()->format('Y-\WW');

        if ($isCurrentWeek) {
            $data = $this->getLiveData($site, $year, $week, $validated['configuration_id'] ?? null);
        } else {
            $data = $this->getArchivedData($site, $year, $week, $validated['configuration_id'] ?? null);
        }

        return new SiteHistoryResource($data);
    }

    /**
     * Fetch and aggregate data from live CheckResult table.
     */
    private function getLiveData(Site $site, int $year, int $week, ?int $configId): array
    {
        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        $query = CheckResult::where('site_id', $site->id)
            ->whereBetween('checked_at', [$startOfWeek, $endOfWeek]);

        if ($configId) {
            $query->where('configuration_id', $configId);
        }

        // Aggregate hourly
        $stats = (clone $query)
            ->select([
                DB::raw("date_trunc('hour', checked_at) as hour"),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(*) as total_checks'),
                DB::raw("COUNT(*) FILTER (WHERE status = 'up') as up_checks"),
            ])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($row) => [
                'timestamp' => Carbon::parse($row->hour)->toIso8601String(),
                'avg_response_time' => round((float)$row->avg_response_time, 2),
                'uptime_percentage' => round(($row->up_checks / $row->total_checks) * 100, 2),
                'count' => $row->total_checks,
            ]);

        // Incidents
        $incidents = $query->where('status', 'down')
            ->orderBy('checked_at', 'desc')
            ->get();

        return compact('stats', 'incidents');
    }

    /**
     * Fetch and aggregate data from CheckResultArchive table.
     */
    private function getArchivedData(Site $site, int $year, int $week, ?int $configId): array
    {
        $query = CheckResultArchive::where('site_id', $site->id)
            ->where('year', $year)
            ->where('week', $week);

        if ($configId) {
            $query->where('configuration_id', $configId);
        }

        $archives = $query->get();

        $allData = $archives->flatMap(fn($a) => $a->data);

        if ($allData->isEmpty()) {
            return ['stats' => [], 'incidents' => []];
        }

        // Aggregate in PHP since it's a JSON array
        $stats = $allData->groupBy(function ($item) {
            return Carbon::parse(data_get($item, 'checked_at'))->startOfHour()->toIso8601String();
        })
            ->map(function ($hourGroup, $hour) {
                $totalCount = $hourGroup->count();
                $upCount = $hourGroup->where('status', 'up')->count();
                $avgResponse = $hourGroup->avg('response_time_ms');

                return [
                    'timestamp' => $hour,
                    'avg_response_time' => round((float)$avgResponse, 2),
                    'uptime_percentage' => round(($upCount / $totalCount) * 100, 2),
                    'count' => $totalCount,
                ];
            })
            ->values()
            ->sortBy('timestamp')
            ->values();

        $incidents = $allData->where('status', 'down')
            ->sortByDesc('checked_at')
            ->values();

        return ['stats' => $stats, 'incidents' => $incidents];
    }
}
