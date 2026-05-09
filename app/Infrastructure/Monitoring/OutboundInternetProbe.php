<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use Illuminate\Support\Facades\Http;
use Throwable;

final class OutboundInternetProbe
{
    public function isReachable(): bool
    {
        if (! config('monitoring.scheduler.internet_check_enabled', true)) {
            return true;
        }

        $url = (string) config('monitoring.scheduler.internet_probe_url', 'https://www.cloudflare.com');
        $timeout = (int) config('monitoring.scheduler.internet_probe_timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['User-Agent' => 'DigiPulse-Scheduler/Connectivity-Probe'])
                ->get($url);

            return $response->status() > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
