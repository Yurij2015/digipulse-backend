<?php

namespace App\Domain\Monitoring\Contracts;

interface CachePortInterface
{
    public const string SITES_CACHE_VERSION = 'v9';

    /**
     * Clear the cache for a specific user's sites.
     */
    public function clearUserSitesCache(int $userId): void;
}
