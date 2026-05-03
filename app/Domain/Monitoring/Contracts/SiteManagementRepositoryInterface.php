<?php

namespace App\Domain\Monitoring\Contracts;

use App\Domain\Monitoring\Data\CreateSiteData;
use App\Domain\Monitoring\Models\Site;

interface SiteManagementRepositoryInterface
{
    /**
     * Find a site by ID.
     */
    public function findById(int $id): ?Site;

    /**
     * Get all sites for a user.
     *
     * @return Site[]
     */
    public function findByUser(int $userId): array;

    /**
     * Create a new site.
     */
    public function create(CreateSiteData $dto): Site;

    /**
     * Update an existing site.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Site;

    /**
     * Sync site check configurations.
     *
     * @param  array<int, array<string, mixed>>  $configurations
     */
    public function syncConfigurations(int $siteId, array $configurations): void;

    /**
     * Delete a site by ID.
     */
    public function delete(int $id): bool;

    /**
     * Reconstruct a site from array data (e.g. from cache).
     */
    public function fromArray(array $data): Site;
}
