<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring\Redis;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class MonitorHeartbeatProbe
{
    public function readHeartbeatFromRedis(int $thresholdMinutes): array
    {
        $logicalKey = (string) config('monitoring.heartbeat.key', 'go_monitor:last_heartbeat');
        $prefix = (string) config('database.redis.options.prefix', '');
        $prefixedKey = $prefix.$logicalKey;

        $rawValue = Redis::get($logicalKey);
        $lastBeat = is_numeric($rawValue) ? (int) $rawValue : null;
        $ageSeconds = $lastBeat !== null ? time() - $lastBeat : null;

        return [
            'logical_key' => $logicalKey,
            'redis_prefix' => $prefix,
            'prefixed_key' => $prefixedKey,
            'prefixed_value' => $this->stringifyRedisValue($rawValue),
            'last_beat' => $lastBeat,
            'age_seconds' => $ageSeconds,
            'heartbeat_healthy' => $lastBeat !== null && $ageSeconds < $thresholdMinutes * 60,
        ];
    }

    public function lastBeatTimestamp(): ?int
    {
        $thresholdMinutes = (int) config('monitoring.heartbeat.alert_after_minutes', 5);

        return $this->readHeartbeatFromRedis($thresholdMinutes)['last_beat'];
    }

    public function isHealthy(int $thresholdMinutes): bool
    {
        return $this->readHeartbeatFromRedis($thresholdMinutes)['heartbeat_healthy'];
    }

    public function evaluate(int $thresholdMinutes): array
    {
        $redis = $this->readHeartbeatFromRedis($thresholdMinutes);
        $http = $this->probeHttpHealth();
        $checkResults = $this->probeRecentCheckResults($thresholdMinutes);

        $isOperational = $redis['heartbeat_healthy']
            || $checkResults['recent']
            || $http['reachable'];

        return [
            'threshold_minutes' => $thresholdMinutes,
            'redis' => $redis,
            'http' => $http,
            'check_results' => $checkResults,
            'is_operational' => $isOperational,
        ];
    }

    public function isOperational(int $thresholdMinutes): bool
    {
        return $this->evaluate($thresholdMinutes)['is_operational'];
    }

    public function minutesSinceLastBeat(int $fallbackMinutes): int
    {
        $thresholdMinutes = (int) config('monitoring.heartbeat.alert_after_minutes', 5);
        $lastBeat = $this->readHeartbeatFromRedis($thresholdMinutes)['last_beat'];

        if ($lastBeat === null) {
            return $fallbackMinutes;
        }

        return (int) round((time() - $lastBeat) / 60);
    }

    public function logProbeCycle(array $evaluation): void
    {
        $context = [
            'go_monitor' => [
                'threshold_minutes' => $evaluation['threshold_minutes'],
                'is_operational' => $evaluation['is_operational'],
                'redis_read' => [
                    'logical_key' => $evaluation['redis']['logical_key'],
                    'redis_prefix' => $evaluation['redis']['redis_prefix'],
                    'prefixed_key' => $evaluation['redis']['prefixed_key'],
                    'prefixed_value' => $evaluation['redis']['prefixed_value'],
                    'last_beat' => $evaluation['redis']['last_beat'],
                    'age_seconds' => $evaluation['redis']['age_seconds'],
                    'heartbeat_healthy' => $evaluation['redis']['heartbeat_healthy'],
                ],
                'http_health' => $evaluation['http'],
                'check_results' => $evaluation['check_results'],
            ],
        ];

        if ($evaluation['is_operational']) {
            if (config('monitoring.heartbeat.log_reads', false)) {
                Log::info('Go monitor liveness probe (operational)', $context);
            }

            return;
        }

        Log::error('Go monitor liveness probe (not operational)', $context);
    }

    public function probeHttpHealth(): array
    {
        $url = (string) config('monitoring.health.url', '');

        if ($url === '') {
            return [
                'url' => '',
                'reachable' => false,
                'status' => null,
                'error' => 'MONITOR_HEALTH_URL is empty',
            ];
        }

        try {
            $response = Http::timeout((int) config('monitoring.health.timeout_seconds', 5))
                ->get($url);

            return [
                'url' => $url,
                'reachable' => $response->successful(),
                'status' => $response->status(),
                'error' => $response->successful() ? null : 'non-success HTTP status',
            ];
        } catch (\Throwable $exception) {
            return [
                'url' => $url,
                'reachable' => false,
                'status' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function isHttpReachable(): bool
    {
        return $this->probeHttpHealth()['reachable'];
    }

    public function probeRecentCheckResults(int $thresholdMinutes): array
    {
        $recent = DB::table('check_results')
            ->join(
                'site_check_configurations',
                'site_check_configurations.id',
                '=',
                'check_results.configuration_id'
            )
            ->where('site_check_configurations.is_active', true)
            ->where('check_results.checked_at', '>=', now()->subMinutes($thresholdMinutes))
            ->exists();

        return [
            'recent' => $recent,
            'threshold_minutes' => $thresholdMinutes,
        ];
    }

    public function hasRecentCheckResults(int $thresholdMinutes): bool
    {
        return $this->probeRecentCheckResults($thresholdMinutes)['recent'];
    }

    private function stringifyRedisValue(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        return (string) $value;
    }
}
