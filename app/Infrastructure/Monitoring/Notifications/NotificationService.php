<?php

namespace App\Infrastructure\Monitoring\Notifications;

use App\Domain\Monitoring\Contracts\AlertServiceInterface;
use App\Models\SiteCheckConfiguration;
use App\Notifications\SiteDownNotification;
use App\Notifications\SiteUpNotification;
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

        try {
            $config->site->user->notify(new SiteDownNotification($config->site));
        } catch (\Throwable $e) {
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

        try {
            $config->site->user->notify(new SiteUpNotification($config->site));
        } catch (\Throwable $e) {
            Log::error('NotificationService: failed to send up alert', [
                'configuration_id' => $configurationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
