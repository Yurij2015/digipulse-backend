<?php

namespace App\Domain\Monitoring\Data;

/**
 * Data Object for monitoring results.
 * This class is pure PHP and has no dependencies on the framework.
 */
readonly class MonitoringResultData
{
    public function __construct(
        public int $configurationId,
        public string $status,
        public ?int $responseTimeMs = null,
        public ?string $errorMessage = null,
        public ?array $metadata = null,
        public ?int $siteId = null,
    ) {}
}
