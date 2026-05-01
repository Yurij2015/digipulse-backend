<?php

namespace App\Domain\Monitoring\Contracts;

interface CachePortInterface
{
    /**
     * Clear the cache for a specific user's sites.
     */
    public function clearUserSitesCache(int $userId): void;
}
