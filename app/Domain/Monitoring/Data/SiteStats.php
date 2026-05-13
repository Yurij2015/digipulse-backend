<?php

namespace App\Domain\Monitoring\Data;

readonly class SiteStats
{
    public function __construct(
        public float $uptime,
        public array $responseTimeHistory,
        public array $dailyUptimeHistory,
        public float $apdexScore,
        public ?int $p95ResponseTime,
    ) {}
}
