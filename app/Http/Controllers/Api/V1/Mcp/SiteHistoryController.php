<?php

namespace App\Http\Controllers\Api\V1\Mcp;

use App\Http\Controllers\Controller;
use App\Models\CheckResult;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteHistoryController extends Controller
{
    public function __invoke(Request $request, int $siteId): JsonResponse
    {
        $site = Site::where('id', $siteId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'granularity' => ['nullable', 'in:hour,day'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::now()->startOfWeek();

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        $granularity = $validated['granularity'] ?? 'hour';
        $truncate = $granularity === 'day' ? 'day' : 'hour';

        $query = CheckResult::where('site_id', $site->id)
            ->whereBetween('checked_at', [$from, $to]);

        $stats = (clone $query)
            ->select([
                DB::raw("date_trunc('{$truncate}', checked_at) as bucket"),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(*) as total_checks'),
                DB::raw("COUNT(*) FILTER (WHERE status = 'up') as up_checks"),
            ])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row) => [
                'timestamp' => Carbon::parse($row->bucket)->toIso8601String(),
                'avg_response_time' => round((float) $row->avg_response_time, 2),
                'uptime_percentage' => round(($row->up_checks / $row->total_checks) * 100, 2),
                'count' => $row->total_checks,
            ]);

        $incidents = (clone $query)
            ->where('status', 'down')
            ->orderBy('checked_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'checked_at' => $r->checked_at->toIso8601String(),
                'response_time_ms' => $r->response_time_ms,
                'error' => $r->error_message,
            ]);

        return response()->json([
            'data' => [
                'site_id' => $site->id,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'granularity' => $granularity,
                'stats' => $stats,
                'incidents' => $incidents,
            ],
        ]);
    }
}