<?php

namespace App\Infrastructure\Monitoring\Cache;

use App\Domain\Monitoring\Contracts\CachePortInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheService implements CachePortInterface
{
    public function clearUserSitesCache(int $userId): void
    {
        $version = CachePortInterface::SITES_CACHE_VERSION;
        Cache::forget("user_sites_{$version}:{$userId}");

        try {
            $projectIds = DB::table('projects')
                ->where('user_id', $userId)
                ->pluck('id')
                ->toArray();

            foreach ($projectIds as $projectId) {
                Cache::forget("user_sites_{$version}:{$userId}:project_{$projectId}");
            }
        } catch (\Throwable $e) {
            // Ignore DB/cache issues
        }
    }
}
