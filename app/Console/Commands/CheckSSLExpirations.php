<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Notifications\SSLExpiringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

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
            ->with('user')
            ->get();

        $count = 0;

        /** @var Site $site */
        foreach ($sites as $site) {
            $latestResult = $site->checks()
                ->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'ssl'))
                ->latest('checked_at')
                ->first();

            if ($latestResult && isset($latestResult->metadata['days_remaining'])) {
                $days = (int) $latestResult->metadata['days_remaining'];

                // Only notify if expires in less than 7 days
                if ($days < 7 && $site->user) {
                    $site->user->notify(new SSLExpiringNotification($site, $days));
                    $this->line("Notified owner of {$site->url} ({$days} days remaining)");
                    $count++;
                }
            }
        }

        $this->info("Completed. Sent {$count} notifications.");
    }
}
