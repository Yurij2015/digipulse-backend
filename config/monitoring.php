<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Scheduler connectivity
    |--------------------------------------------------------------------------
    |
    | Before pushing check jobs to Redis, app:schedule-checks can verify
    | outbound internet (same env keys as the Go monitor worker by default).
    |
    */
    'scheduler' => [
        'internet_check_enabled' => filter_var(
            env('INTERNET_CHECK_ENABLED', 'true'),
            FILTER_VALIDATE_BOOLEAN
        ),
        'internet_probe_url' => env('INTERNET_PROBE_URL', 'https://www.cloudflare.com'),
        'internet_probe_timeout' => (int) env('INTERNET_PROBE_TIMEOUT_SEC', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitor results consumer
    |--------------------------------------------------------------------------
    |
    | app:consume-monitor-results reads check results produced by the Go
    | monitor service from Redis and persists them using domain use cases.
    |
    */
    'results_consumer' => [
        'enabled' => filter_var(env('MONITOR_RESULTS_CONSUMER_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
        'queue' => env('MONITOR_RESULTS_QUEUE', 'monitoring:results'),
        'failed_queue' => env('MONITOR_RESULTS_FAILED_QUEUE', 'monitoring:results:failed'),
        'block_seconds' => max(1, (int) env('MONITOR_RESULTS_CONSUMER_BLOCK_SECONDS', 5)),
        'max_attempts' => max(1, (int) env('MONITOR_RESULTS_CONSUMER_MAX_ATTEMPTS', 5)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Go monitor heartbeat (Redis)
    |--------------------------------------------------------------------------
    |
    | Go writes the full key (REDIS_PREFIX + key) via MONITOR_HEARTBEAT_KEY.
    | Laravel reads the logical key below; the Redis client adds REDIS_PREFIX.
    |
    */
    'heartbeat' => [
        'key' => 'go_monitor:last_heartbeat',
        'alert_after_minutes' => max(1, (int) env('MONITOR_HEARTBEAT_ALERT_AFTER_MINUTES', 5)),
        'alert_throttle_minutes' => max(1, (int) env('MONITOR_HEARTBEAT_ALERT_THROTTLE_MINUTES', 30)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Go monitor HTTP health (active liveness probe from Laravel)
    |--------------------------------------------------------------------------
    |
    | When the Redis heartbeat is stale, Laravel calls the monitor /health
    | endpoint before alerting. Prevents false positives when checks still run.
    |
    */
    'health' => [
        'url' => env('MONITOR_HEALTH_URL', 'http://digipulse-monitor:8080/health'),
        'timeout_seconds' => max(1, (int) env('MONITOR_HEALTH_TIMEOUT_SEC', 5)),
    ],

    'site_limits' => [
        'default' => (int) env('SITE_LIMIT_DEFAULT', 6),
        'agency' => (int) env('SITE_LIMIT_AGENCY', 60),
    ],
];
