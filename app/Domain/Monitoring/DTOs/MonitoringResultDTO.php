<?php

namespace App\Domain\Monitoring\DTOs;

/**
 * Data Transfer Object for monitoring results.
 * This class is pure PHP and has no dependencies on the framework.
 */
readonly class MonitoringResultDTO
{
    public function __construct(
        public int $configurationId,
        public string $status,
        public ?int $responseTimeMs = null,
        public ?string $errorMessage = null,
        public ?array $metadata = null,
        public ?int $siteId = null,
    ) {}

    /**
     * Create DTO from array (useful for controllers/webhooks).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            configurationId: $data['configuration_id'],
            status: $data['status'],
            responseTimeMs: $data['response_time_ms'] ?? null,
            errorMessage: $data['error_message'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
