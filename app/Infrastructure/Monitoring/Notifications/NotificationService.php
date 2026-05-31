<?php

namespace App\Infrastructure\Monitoring\Notifications;

use App\Domain\Monitoring\Contracts\AlertServiceInterface;
use App\Models\SiteCheckConfiguration;
use App\Notifications\SiteDownNotification;
use App\Notifications\SiteUpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationService implements AlertServiceInterface
{
    public function sendSiteDownAlert(int $configurationId): void
    {
        $config = SiteCheckConfiguration::with('site.user')->find($configurationId);

        if (! $config?->site?->user) {
            Log::warning('NotificationService: skipping down alert, config/site/user not found', [
                'configuration_id' => $configurationId,
            ]);

            return;
        }

        $siteId = $config->site_id;

        // One down alert per site per hour — prevents duplicate alerts from multiple check configurations.
        if (! Cache::add("site_alerted_down:{$siteId}", true, now()->addHour())) {
            Log::info('NotificationService: down alert suppressed (already sent recently)', [
                'configuration_id' => $configurationId,
                'site_id' => $siteId,
            ]);

            return;
        }

        try {
            $config->site->user->notify(new SiteDownNotification($config->site));
        } catch (\Throwable $e) {
            Cache::forget("site_alerted_down:{$siteId}");
            Log::error('NotificationService: failed to send down alert', [
                'configuration_id' => $configurationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendSiteUpAlert(int $configurationId): void
    {
        $config = SiteCheckConfiguration::with('site.user')->find($configurationId);

        if (! $config?->site?->user) {
            Log::warning('NotificationService: skipping up alert, config/site/user not found', [
                'configuration_id' => $configurationId,
            ]);

            return;
        }

        $siteId = $config->site_id;

        // One recovery alert per site per 5 minutes — deduplicates simultaneous recoveries from multiple configs.
        // Also clears the down-alert gate so the next outage can alert again.
        if (! Cache::add("site_alerted_up:{$siteId}", true, now()->addMinutes(5))) {
            Log::info('NotificationService: up alert suppressed (already sent recently)', [
                'configuration_id' => $configurationId,
                'site_id' => $siteId,
            ]);

            return;
        }

        Cache::forget("site_alerted_down:{$siteId}");

        try {
            $config->site->user->notify(new SiteUpNotification($config->site));
        } catch (\Throwable $e) {
            Cache::forget("site_alerted_up:{$siteId}");
            Log::error('NotificationService: failed to send up alert', [
                'configuration_id' => $configurationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
