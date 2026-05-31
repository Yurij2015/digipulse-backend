<?php

namespace App\Infrastructure\Monitoring\Cache;

use App\Domain\Monitoring\Contracts\CachePortInterface;
use Illuminate\Support\Facades\Cache;

class CacheService implements CachePortInterface
{
    public function clearUserSitesCache(int $userId): void
    {
        // Bumping the generation key invalidates all page/count/status cache entries
        // for this user without needing wildcard deletes in Redis.
        Cache::increment("user_sites_gen:{$userId}");
    }
}
