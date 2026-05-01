<?php

namespace App\Infrastructure\Monitoring\Notifications;

use App\Domain\Monitoring\Contracts\AlertServiceInterface;
use App\Models\SiteCheckConfiguration;
use App\Notifications\SiteDownNotification;

class NotificationService implements AlertServiceInterface
{
    public function sendSiteDownAlert(int $configurationId): void
    {
        $config = SiteCheckConfiguration::with('site.user')->findOrFail($configurationId);

        if ($config->site->user) {
            $config->site->user->notify(new SiteDownNotification($config->site));
        }
    }
}
