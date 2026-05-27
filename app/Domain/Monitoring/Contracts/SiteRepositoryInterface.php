<?php

namespace App\Domain\Monitoring\Contracts;

interface SiteRepositoryInterface
{
    /**
     * Update status, failure counter, and confirmation timestamp for a configuration.
     */
    public function updateStatus(
        int $configurationId,
        string $status,
        int $consecutiveFailures,
        ?\DateTimeInterface $confirmedDownAt,
    ): void;

    /**
     * Get context data needed for processing a monitoring result.
     *
     * @return array{site_id: int, user_id: int, last_status: ?string, consecutive_failures: int, confirmed_down_at: ?string}
     */
    public function getConfigurationContext(int $configurationId): array;
}
