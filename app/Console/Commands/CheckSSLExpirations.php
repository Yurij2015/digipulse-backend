<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Notifications\SSLExpiringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('app:check-ssl-expirations')]
#[Description('Scan monitored sites for SSL certificates expiring within 7 days and notify owners.')]
class CheckSSLExpirations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting SSL expiration check...');

        $sites = Site::where('is_active', true)
            ->withWhereHas('configurations', fn ($q) => $q->whereRelation('checkType', 'slug', 'ssl'))
            ->with(['user', 'latestSslCheck'])
            ->get();

        $count = 0;

        /** @var Site $site */
        foreach ($sites as $site) {
            // Use eager-loaded relation to avoid N+1 queries
            $latestResult = $site->latestSslCheck;

            if ($latestResult && isset($latestResult->metadata['days_remaining'])) {
                $days = (int) $latestResult->metadata['days_remaining'];

                if ($days < 7 && $site->user) {
                    // Deduplicate: only send one notification per site per day
                    $cacheKey = "ssl_notified:{$site->id}:" . now()->format('Y-m-d');
                    if (Cache::has($cacheKey)) {
                        $this->line("Skipping {$site->url} — already notified today");
                        continue;
                    }

                    $site->user->notify(new SSLExpiringNotification($site, $days));
                    Cache::put($cacheKey, true, now()->endOfDay());

                    $this->line("Notified owner of {$site->url} ({$days} days remaining)");
                    $count++;
                }
            }
        }

        $this->info("Completed. Sent {$count} notifications.");
    }
}
