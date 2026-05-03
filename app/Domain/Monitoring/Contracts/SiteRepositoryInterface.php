<?php

namespace App\Domain\Monitoring\Contracts;

interface SiteRepositoryInterface
{
    /**
     * Update the status and last check time for a site configuration.
     */
    public function updateStatus(int $configurationId, string $status): void;

    /**
     * Get context data needed for processing a monitoring result.
     *
     * @return array{site_id: int, user_id: int, last_status: ?string}
     */
    public function getConfigurationContext(int $configurationId): array;
}
