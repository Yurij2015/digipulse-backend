<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring\Redis;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Determines whether the Go monitor is operational (Redis heartbeat, HTTP, or DB activity).
 */
final class MonitorHeartbeatProbe
{
    public function lastBeatTimestamp(): ?int
    {
        $logicalKey = (string) config('monitoring.heartbeat.key', 'go_monitor:last_heartbeat');

        $value = Redis::get($logicalKey);

        return is_numeric($value) ? (int) $value : null;
    }

    public function isHealthy(int $thresholdMinutes): bool
    {
        $lastBeat = $this->lastBeatTimestamp();

        return $lastBeat !== null && (time() - $lastBeat) < $thresholdMinutes * 60;
    }

    /**
     * Monitor is considered up when any liveness signal succeeds (not only Redis heartbeat).
     */
    public function isOperational(int $thresholdMinutes): bool
    {
        if ($this->isHealthy($thresholdMinutes)) {
            return true;
        }

        if ($this->hasRecentCheckResults($thresholdMinutes)) {
            return true;
        }

        return $this->isHttpReachable();
    }

    public function minutesSinceLastBeat(int $fallbackMinutes): int
    {
        $lastBeat = $this->lastBeatTimestamp();

        if ($lastBeat === null) {
            return $fallbackMinutes;
        }

        return (int) round((time() - $lastBeat) / 60);
    }

    /**
     * Active probe: GET the Go monitor /health endpoint from the app network.
     */
    public function isHttpReachable(): bool
    {
        $url = (string) config('monitoring.health.url', '');

        if ($url === '') {
            return false;
        }

        try {
            $response = Http::timeout((int) config('monitoring.health.timeout_seconds', 5))
                ->get($url);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * True when Go monitor + Laravel consumer processed checks recently.
     */
    public function hasRecentCheckResults(int $thresholdMinutes): bool
    {
        return DB::table('check_results')
            ->join(
                'site_check_configurations',
                'site_check_configurations.id',
                '=',
                'check_results.configuration_id'
            )
            ->where('site_check_configurations.is_active', true)
            ->where('check_results.checked_at', '>=', now()->subMinutes($thresholdMinutes))
            ->exists();
    }

    public function logDiagnostics(): void
    {
        $thresholdMinutes = (int) config('monitoring.heartbeat.alert_after_minutes', 5);

        Log::warning('Monitor appears down after liveness checks', [
            'redis_prefix' => config('database.redis.options.prefix'),
            'heartbeat_key' => config('monitoring.heartbeat.key', 'go_monitor:last_heartbeat'),
            'last_beat' => $this->lastBeatTimestamp(),
            'http_url' => config('monitoring.health.url'),
            'http_reachable' => $this->isHttpReachable(),
            'recent_check_results' => $this->hasRecentCheckResults($thresholdMinutes),
        ]);
    }
}
