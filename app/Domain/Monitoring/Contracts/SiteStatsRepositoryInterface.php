<?php

namespace App\Domain\Monitoring\Contracts;

use App\Domain\Monitoring\Data\SiteStats;

interface SiteStatsRepositoryInterface
{
    public function loadForSite(int $siteId, int $updateInterval): SiteStats;

    /**
     * @param  array<int, int>  $siteIntervals  keyed by site_id, value is update_interval in minutes
     * @return array<int, SiteStats>
     */
    public function loadForSites(array $siteIntervals): array;

    public function clearCache(int $siteId): void;
}
