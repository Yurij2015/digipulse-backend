<?php

namespace App\Infrastructure\Monitoring\Cache;

use App\Domain\Monitoring\Contracts\CachePortInterface;
use Illuminate\Support\Facades\Cache;

class CacheService implements CachePortInterface
{
    public function clearUserSitesCache(int $userId): void
    {
        Cache::forget("user_sites_v3:{$userId}");
    }
}
