<?php

namespace App\Console\Commands;

use App\Models\SiteCheckConfiguration;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

#[Signature('app:schedule-checks')]
#[Description('Scan due checks and push them to the monitor queue.')]
class ScheduleChecks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $dueConfigs = SiteCheckConfiguration::dueForCheck()
            ->with(['site', 'checkType'])
            ->get();

        if ($dueConfigs->isEmpty()) {
            $this->info('No checks are due at this time.');

            return;
        }

        foreach ($dueConfigs as $config) {
            $payload = json_encode([
                'id' => (string) str()->uuid(),
                'configuration_id' => $config->id,
                'site_id' => $config->site_id,
                'url' => $config->site->url,
                'type' => $config->checkType->slug,
                'params' => $config->params,
                'scheduled_at' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR);

            Redis::lpush('monitoring:tasks', $payload);

            $this->line(sprintf(
                'Scheduled [%s] check for Site: %s (ID: %d)',
                strtoupper($config->checkType->slug),
                $config->site->url,
                $config->id
            ));
        }

        $this->info(sprintf('Successfully scheduled %d check(s).', $dueConfigs->count()));
    }
}
