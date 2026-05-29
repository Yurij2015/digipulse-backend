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
        $retries = (int) config('monitoring.scheduler.internet_probe_retries', 3);

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::timeout($timeout)
                    ->withHeaders(['User-Agent' => 'DigiPulse-Scheduler/Connectivity-Probe'])
                    ->get($url);

                if ($response->status() > 0) {
                    return true;
                }
            } catch (Throwable) {
                // fall through to retry
            }

            if ($attempt < $retries) {
                sleep(2);
            }
        }

        return false;
    }
}
