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
        if (empty($siteIntervals)) {
            return [];
        }

        $siteIds = array_keys($siteIntervals);

        // One Redis round-trip for all 5 keys × all sites
        $allKeys = array_merge(
            ...array_map(
            static fn(int $id) => [
                "site_{$id}_uptime_v1",
                "site_{$id}_rt_history_v1",
                "site_{$id}_daily_history_v1",
                "site_{$id}_apdex_v1",
                "site_{$id}_p95_v1",
            ],
            $siteIds,
        )
        );
        $cached = Cache::many($allKeys);

        $uncachedIds = array_values(
            array_filter(
                $siteIds,
                static fn(int $id) => $cached["site_{$id}_uptime_v1"] === null
                    || $cached["site_{$id}_rt_history_v1"] === null
                    || $cached["site_{$id}_daily_history_v1"] === null
                    || $cached["site_{$id}_apdex_v1"] === null
                    || $cached["site_{$id}_p95_v1"] === null,
            )
        );

        if (!empty($uncachedIds)) {
            $since = now()->subDays(30);

            // 5 queries total regardless of how many sites are uncached
            $uptimes = $this->batchComputeUptime($uncachedIds, $since);
            $rtHistories = $this->batchComputeResponseTimeHistory($uncachedIds);
            $dailies = $this->batchComputeDailyUptimeHistory($uncachedIds, $since);
            $apdexScores = $this->batchComputeApdexScore($uncachedIds, $since);
            $p95Times = $this->batchComputeP95ResponseTime($uncachedIds, $since);

            foreach ($uncachedIds as $id) {
                $ttl = $this->ttl($siteIntervals[$id]);
                $ttl2 = $this->ttl($siteIntervals[$id], 2);

                Cache::put("site_{$id}_uptime_v1", $uptimes[$id], $ttl);
                Cache::put("site_{$id}_rt_history_v1", $rtHistories[$id], $ttl);
                Cache::put("site_{$id}_daily_history_v1", $dailies[$id], $ttl2);
                Cache::put("site_{$id}_apdex_v1", $apdexScores[$id], $ttl2);
                Cache::put("site_{$id}_p95_v1", $p95Times[$id], $ttl2);

                $cached["site_{$id}_uptime_v1"] = $uptimes[$id];
                $cached["site_{$id}_rt_history_v1"] = $rtHistories[$id];
                $cached["site_{$id}_daily_history_v1"] = $dailies[$id];
                $cached["site_{$id}_apdex_v1"] = $apdexScores[$id];
                $cached["site_{$id}_p95_v1"] = $p95Times[$id];
            }
        }

        $result = [];
        foreach ($siteIds as $id) {
            $result[$id] = new SiteStats(
                uptime: (float)$cached["site_{$id}_uptime_v1"],
                responseTimeHistory: (array)$cached["site_{$id}_rt_history_v1"],
                dailyUptimeHistory: (array)$cached["site_{$id}_daily_history_v1"],
                apdexScore: (float)$cached["site_{$id}_apdex_v1"],
                p95ResponseTime: $cached["site_{$id}_p95_v1"] !== null ? (int)$cached["site_{$id}_p95_v1"] : null,
            );
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
        return Cache::remember(
            "site_{$siteId}_rt_history_v1",
            $this->ttl($updateInterval),
            static function () use ($siteId) {
                return CheckResult::where('check_results.site_id', $siteId)
                    ->join(
                        'site_check_configurations',
                        'site_check_configurations.id',
                        '=',
                        'check_results.configuration_id'
                    )
                    ->join('check_types', 'check_types.id', '=', 'site_check_configurations.check_type_id')
                    ->where('check_types.slug', 'http')
                    ->latest('check_results.checked_at')
                    ->limit(12)
                    ->pluck('check_results.response_time_ms')
                    ->reverse()
                    ->values()
                    ->toArray();
            }
        );
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

    // -------------------------------------------------------------------------
    // Batch helpers — used by loadForSites to run 5 queries for N sites at once
    // -------------------------------------------------------------------------

    /** @param int[] $siteIds */
    private function batchComputeUptime(array $siteIds, mixed $since): array
    {
        $archiveSince = now()->subDays(37);
        $ph = $this->placeholders($siteIds);

        $rows = DB::select(
            "
            WITH live AS (
                SELECT site_id, status
                FROM check_results
                WHERE site_id IN ({$ph}) AND checked_at >= ?
            ),
            archived AS (
                SELECT site_id, elem->>'status' AS status
                FROM check_result_archives,
                     json_array_elements(data) AS elem
                WHERE site_id IN ({$ph})
                  AND created_at >= ?
                  AND (elem->>'checked_at')::timestamptz >= ?
            ),
            combined AS (
                SELECT site_id, status FROM live
                UNION ALL
                SELECT site_id, status FROM archived
            )
            SELECT
                site_id,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) AS up_count
            FROM combined
            GROUP BY site_id
        ",
            [...$siteIds, $since, ...$siteIds, $archiveSince, $since]
        );

        $result = array_fill_keys($siteIds, 100.0);
        foreach ($rows as $row) {
            $total = (int)$row->total;
            $result[(int)$row->site_id] = $total === 0
                ? 100.0
                : round(((int)$row->up_count / $total) * 100, 2);
        }

        return $result;
    }

    /** @param int[] $siteIds */
    private function batchComputeResponseTimeHistory(array $siteIds): array
    {
        $ph = $this->placeholders($siteIds);

        $rows = DB::select(
            "
            SELECT site_id, response_time_ms, checked_at
            FROM (
                SELECT
                    cr.site_id,
                    cr.response_time_ms,
                    cr.checked_at,
                    ROW_NUMBER() OVER (PARTITION BY cr.site_id ORDER BY cr.checked_at DESC) AS rn
                FROM check_results cr
                JOIN site_check_configurations scc ON scc.id = cr.configuration_id
                JOIN check_types ct ON ct.id = scc.check_type_id AND ct.slug = 'http'
                WHERE cr.site_id IN ({$ph})
            ) ranked
            WHERE rn <= 12
            ORDER BY site_id, checked_at
        ",
            $siteIds
        );

        $result = array_fill_keys($siteIds, []);
        foreach ($rows as $row) {
            $result[(int)$row->site_id][] = $row->response_time_ms;
        }

        return $result;
    }

    /** @param int[] $siteIds */
    private function batchComputeDailyUptimeHistory(array $siteIds, mixed $since): array
    {
        $archiveSince = now()->subDays(37);
        $seriesStart = now()->subDays(29)->toDateString();
        $ph = $this->placeholders($siteIds);

        $rows = DB::select(
            "
            WITH dates AS (
                SELECT generate_series(
                    ?::date,
                    CURRENT_DATE,
                    INTERVAL '1 day'
                )::date AS day
            ),
            site_list AS (
                SELECT unnest(ARRAY[{$ph}]::int[]) AS site_id
            ),
            live AS (
                SELECT site_id, DATE(checked_at) AS day, status
                FROM check_results
                WHERE site_id IN ({$ph}) AND checked_at >= ?
            ),
            archived AS (
                SELECT site_id, DATE((elem->>'checked_at')::timestamptz) AS day, elem->>'status' AS status
                FROM check_result_archives,
                     json_array_elements(data) AS elem
                WHERE site_id IN ({$ph})
                  AND created_at >= ?
                  AND (elem->>'checked_at')::timestamptz >= ?
            ),
            combined AS (
                SELECT site_id, day, status FROM live
                UNION ALL
                SELECT site_id, day, status FROM archived
            )
            SELECT
                s.site_id,
                d.day::text AS date,
                COUNT(c.status) AS total_checks,
                SUM(CASE WHEN c.status = 'up' THEN 1 ELSE 0 END) AS up_count
            FROM site_list s
            CROSS JOIN dates d
            LEFT JOIN combined c ON c.day = d.day AND c.site_id = s.site_id
            GROUP BY s.site_id, d.day
            ORDER BY s.site_id, d.day
        ",
            [$seriesStart, ...$siteIds, ...$siteIds, $since, ...$siteIds, $archiveSince, $since]
        );

        $result = array_fill_keys($siteIds, []);
        foreach ($rows as $row) {
            $total = (int)$row->total_checks;
            $result[(int)$row->site_id][] = [
                'date' => $row->date,
                'uptime' => $total > 0 ? round(((int)$row->up_count / $total) * 100, 2) : 100.0,
                'total_checks' => $total,
            ];
        }

        return $result;
    }

    /** @param int[] $siteIds */
    private function batchComputeApdexScore(array $siteIds, mixed $since): array
    {
        $ph = $this->placeholders($siteIds);

        $rows = DB::select(
            "
            SELECT
                site_id,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'up' AND response_time_ms <= 300 THEN 1 ELSE 0 END) AS satisfied,
                SUM(CASE WHEN status = 'up' AND response_time_ms > 300 AND response_time_ms <= 1200 THEN 1 ELSE 0 END) AS tolerating
            FROM check_results
            WHERE site_id IN ({$ph}) AND checked_at >= ?
            GROUP BY site_id
        ",
            [...$siteIds, $since]
        );

        $result = array_fill_keys($siteIds, 1.0);
        foreach ($rows as $row) {
            $total = (int)$row->total;
            if ($total > 0) {
                $result[(int)$row->site_id] = round(
                    ((int)$row->satisfied + ((int)$row->tolerating / 2)) / $total,
                    2,
                );
            }
        }

        return $result;
    }

    /** @param int[] $siteIds */
    private function batchComputeP95ResponseTime(array $siteIds, mixed $since): array
    {
        $ph = $this->placeholders($siteIds);

        $rows = DB::select(
            "
            SELECT
                site_id,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_time_ms) AS p95
            FROM check_results
            WHERE site_id IN ({$ph})
              AND checked_at >= ?
              AND status = 'up'
              AND response_time_ms IS NOT NULL
            GROUP BY site_id
        ",
            [...$siteIds, $since]
        );

        $result = array_fill_keys($siteIds, null);
        foreach ($rows as $row) {
            $result[(int)$row->site_id] = $row->p95 !== null ? (int)$row->p95 : null;
        }

        return $result;
    }

    private function placeholders(array $ids): string
    {
        return implode(',', array_fill(0, count($ids), '?'));
    }
}
