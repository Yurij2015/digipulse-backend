<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\SiteStatsRepositoryInterface;
use App\Domain\Monitoring\Data\SiteStats;
use App\Models\CheckResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EloquentSiteStatsRepository implements SiteStatsRepositoryInterface
{
    public function loadForSite(int $siteId, int $updateInterval): SiteStats
    {
        return new SiteStats(
            uptime: $this->computeUptime($siteId, $updateInterval),
            responseTimeHistory: $this->computeResponseTimeHistory($siteId, $updateInterval),
            dailyUptimeHistory: $this->computeDailyUptimeHistory($siteId, $updateInterval),
            apdexScore: $this->computeApdexScore($siteId, $updateInterval),
            p95ResponseTime: $this->computeP95ResponseTime($siteId, $updateInterval),
        );
    }

    public function loadForSites(array $siteIntervals): array
    {
        $result = [];
        foreach ($siteIntervals as $siteId => $updateInterval) {
            $result[$siteId] = $this->loadForSite($siteId, $updateInterval);
        }

        return $result;
    }

    private function ttl(int $updateInterval, int $multiplier = 1): int
    {
        return max($updateInterval * 60 * $multiplier, 60);
    }

    public function clearCache(int $siteId): void
    {
        Cache::forget("site_{$siteId}_uptime_v1");
        Cache::forget("site_{$siteId}_rt_history_v1");
        Cache::forget("site_{$siteId}_daily_history_v1");
        Cache::forget("site_{$siteId}_apdex_v1");
        Cache::forget("site_{$siteId}_p95_v1");
    }

    private function computeUptime(int $siteId, int $updateInterval): float
    {
        return Cache::remember(
            "site_{$siteId}_uptime_v1",
            $this->ttl($updateInterval),
            static function () use ($siteId) {
                $since = now()->subDays(30);
                $archiveSince = $since->copy()->subDays(7);

                $row = DB::selectOne(
                    "
                WITH live AS (
                    SELECT status
                    FROM check_results
                    WHERE site_id = ? AND checked_at >= ?
                ),
                archived AS (
                    SELECT elem->>'status' AS status
                    FROM check_result_archives,
                         json_array_elements(data) AS elem
                    WHERE site_id = ?
                      AND created_at >= ?
                      AND (elem->>'checked_at')::timestamptz >= ?
                ),
                combined AS (
                    SELECT status FROM live
                    UNION ALL
                    SELECT status FROM archived
                )
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) AS up_count
                FROM combined
            ",
                    [$siteId, $since, $siteId, $archiveSince, $since]
                );

                $total = (int)($row->total ?? 0);
                if ($total === 0) {
                    return 100.0;
                }

                return round(((int)($row->up_count ?? 0) / $total) * 100, 2);
            }
        );
    }

    private function computeResponseTimeHistory(int $siteId, int $updateInterval): array
    {
        return Cache::remember("site_{$siteId}_rt_history_v1", $this->ttl($updateInterval), function () use ($siteId) {
            return CheckResult::where('site_id', $siteId)
                ->whereHas('configuration.checkType', fn($q) => $q->where('slug', 'http'))
                ->latest('checked_at')
                ->limit(12)
                ->get()
                ->reverse()
                ->map(fn($check) => $check->response_time_ms)
                ->values()
                ->toArray();
        });
    }

    private function computeDailyUptimeHistory(int $siteId, int $updateInterval): array
    {
        return Cache::remember(
            "site_{$siteId}_daily_history_v1",
            $this->ttl($updateInterval, 2),
            static function () use ($siteId) {
                $since = now()->subDays(30)->startOfDay();
                $archiveSince = $since->copy()->subDays(7);
                $seriesStart = now()->subDays(29)->toDateString();

                $rows = DB::select(
                    "
                WITH dates AS (
                    SELECT generate_series(
                        ?::date,
                        CURRENT_DATE,
                        INTERVAL '1 day'
                    )::date AS day
                ),
                live AS (
                    SELECT DATE(checked_at) AS day, status
                    FROM check_results
                    WHERE site_id = ? AND checked_at >= ?
                ),
                archived AS (
                    SELECT DATE((elem->>'checked_at')::timestamptz) AS day, elem->>'status' AS status
                    FROM check_result_archives,
                         json_array_elements(data) AS elem
                    WHERE site_id = ?
                      AND created_at >= ?
                      AND (elem->>'checked_at')::timestamptz >= ?
                ),
                combined AS (
                    SELECT day, status FROM live
                    UNION ALL
                    SELECT day, status FROM archived
                )
                SELECT
                    d.day::text AS date,
                    COUNT(c.status) AS total_checks,
                    SUM(CASE WHEN c.status = 'up' THEN 1 ELSE 0 END) AS up_count
                FROM dates d
                LEFT JOIN combined c ON c.day = d.day
                GROUP BY d.day
                ORDER BY d.day
            ",
                    [$seriesStart, $siteId, $since, $siteId, $archiveSince, $since]
                );

                return array_map(static fn($row) => [
                    'date' => $row->date,
                    'uptime' => $row->total_checks > 0
                        ? round(((int)$row->up_count / (int)$row->total_checks) * 100, 2)
                        : 100.0,
                    'total_checks' => (int)$row->total_checks,
                ], $rows);
            }
        );
    }

    private function computeApdexScore(int $siteId, int $updateInterval): float
    {
        return Cache::remember("site_{$siteId}_apdex_v1", $this->ttl($updateInterval, 2), function () use ($siteId) {
            $since = now()->subDays(30);

            $stats = CheckResult::where('site_id', $siteId)
                ->where('checked_at', '>=', $since)
                ->selectRaw(
                    "
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'up' AND response_time_ms <= 300 THEN 1 ELSE 0 END) as satisfied,
                    SUM(CASE WHEN status = 'up' AND response_time_ms > 300 AND response_time_ms <= 1200 THEN 1 ELSE 0 END) as tolerating
                "
                )
                ->first();

            $total = (int)($stats->total ?? 0);
            if ($total === 0) {
                return 1.0;
            }

            $satisfied = (int)($stats->satisfied ?? 0);
            $tolerating = (int)($stats->tolerating ?? 0);

            return round(($satisfied + ($tolerating / 2)) / $total, 2);
        });
    }

    private function computeP95ResponseTime(int $siteId, int $updateInterval): ?int
    {
        return Cache::remember("site_{$siteId}_p95_v1", $this->ttl($updateInterval, 2), function () use ($siteId) {
            $since = now()->subDays(30);

            $result = CheckResult::where('site_id', $siteId)
                ->where('checked_at', '>=', $since)
                ->where('status', 'up')
                ->whereNotNull('response_time_ms')
                ->selectRaw('PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_time_ms) AS p95')
                ->value('p95');

            return $result !== null ? (int)$result : null;
        });
    }
}
