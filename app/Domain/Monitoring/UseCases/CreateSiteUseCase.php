<?php

namespace App\Domain\Monitoring\UseCases;

use App\Domain\Monitoring\Contracts\CachePortInterface;
use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Data\CreateSiteData;
use App\Domain\Monitoring\Exceptions\SiteLimitExceededException;
use App\Domain\Monitoring\Models\Site;
use App\Models\User;

/**
 * Use Case for creating a new site with monitoring configurations.
 * Enforces business rules like site limits for non-admin users.
 */
readonly class CreateSiteUseCase
{
    public function __construct(
        private SiteManagementRepositoryInterface $siteRepository,
        private CachePortInterface $cachePort,
    ) {}

    /**
     * Execute the use case.
     *
     * @param  array<int, array<string, mixed>>  $configurations
     *
     * @throws SiteLimitExceededException
     */
    public function execute(CreateSiteData $dto, User $user, array $configurations = []): Site
    {
        // Enforce site limit for regular users
        if (! $user->hasRole('admin')) {
            $siteCount = $this->siteRepository->countByUser($dto->userId);

            if ($siteCount >= 3) {
                throw new SiteLimitExceededException;
            }
        }

        $site = $this->siteRepository->create($dto);

        // Sync configurations if provided
        if (! empty($configurations)) {
            $this->siteRepository->syncConfigurations($site->id, $configurations);

            // Re-fetch site to get updated relations
            $updatedSite = $this->siteRepository->findById($site->id);
            if ($updatedSite) {
                $site = $updatedSite;
            }
        }

        // Invalidate cache
        $this->cachePort->clearUserSitesCache($dto->userId);

        return $site;
    }
}
