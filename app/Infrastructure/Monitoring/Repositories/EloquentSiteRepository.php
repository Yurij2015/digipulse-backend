<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
use App\Models\SiteCheckConfiguration;

class EloquentSiteRepository implements SiteRepositoryInterface
{
    public function updateStatus(int $configurationId, string $status): void
    {
        SiteCheckConfiguration::where('id', $configurationId)->update([
            'last_status' => $status,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * @return array{site_id: int, user_id: int, last_status: ?string}
     */
    public function getConfigurationContext(int $configurationId): array
    {
        $row = SiteCheckConfiguration::join('sites', 'sites.id', '=', 'site_check_configurations.site_id')
            ->where('site_check_configurations.id', $configurationId)
            ->select([
                'site_check_configurations.site_id',
                'site_check_configurations.last_status',
                'sites.user_id',
            ])
            ->firstOrFail();

        return [
            'site_id' => $row->site_id,
            'user_id' => $row->user_id,
            'last_status' => $row->last_status,
        ];
    }

    public function getSiteDetails(int $configurationId): array
    {
        $config = SiteCheckConfiguration::with('site')->findOrFail($configurationId);

        return $config->site->toArray();
    }
}
