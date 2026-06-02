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
     * Get all sites for a user, optionally filtered by project.
     *
     * @return Site[]
     */
    public function findByUser(int $userId, ?int $projectId = null): array;

    /**
     * Get site IDs for a user, optionally filtered by project and/or specific site.
     *
     * @return int[]
     */
    public function findIdsByUser(int $userId, ?int $projectId = null, ?int $siteId = null): array;

    /**
     * Count total sites for a user.
     */
    public function countByUser(int $userId): int;

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
     * Get one page of sites with full data (DB-level pagination).
     *
     * @return Site[]
     */
    public function findPage(int $userId, ?int $projectId, int $perPage, int $page, ?string $status = null): array;

    /**
     * Count total sites matching the user/project filter.
     */
    public function countByFilter(int $userId, ?int $projectId, ?string $status = null): int;

    /**
     * Get status counts for all the user's sites via a single lightweight query.
     * Keys: up, down, slow, pending. Any missing status defaults to 0.
     *
     * @return array{up: int, down: int, slow: int, pending: int}
     */
    public function getStatusCounts(int $userId, ?int $projectId): array;
}
