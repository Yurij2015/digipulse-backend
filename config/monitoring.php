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
        'block_seconds' => max(1, (int) env('MONITOR_RESULTS_CONSUMER_BLOCK_SECONDS', 5)),
    ],
];
