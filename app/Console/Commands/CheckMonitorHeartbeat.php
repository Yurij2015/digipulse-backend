<?php

namespace App\Console\Commands;

use App\Models\SiteCheckConfiguration;
use App\Models\User;
use App\Notifications\MonitorHeartbeatMissingNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

#[Signature('app:check-monitor-heartbeat')]
#[Description('Alert admin if the Go monitor has not sent a heartbeat recently.')]
class CheckMonitorHeartbeat extends Command
{
    private const string HEARTBEAT_KEY = 'go_monitor:last_heartbeat';

    private const int ALERT_AFTER_MINUTES = 5;

    private const string ALERT_THROTTLE_KEY = 'go_monitor:heartbeat_alert_sent';

    private const int ALERT_THROTTLE_MINUTES = 30;

    public function handle(): void
    {
        if (! SiteCheckConfiguration::where('is_active', true)->exists()) {
            return;
        }

        $lastBeat = Redis::get(self::HEARTBEAT_KEY);

        if ($lastBeat && (time() - (int) $lastBeat) < self::ALERT_AFTER_MINUTES * 60) {
            return;
        }

        $minutesSince = $lastBeat
            ? (int) round((time() - (int) $lastBeat) / 60)
            : self::ALERT_AFTER_MINUTES;

        if (Cache::has(self::ALERT_THROTTLE_KEY)) {
            $this->line('Heartbeat missing but alert already sent recently — skipping.');

            return;
        }

        $admin = User::where('email_bindex', User::generateBlindIndex(config('app.admin_email')))->first();

        if ($admin) {
            $admin->notify(new MonitorHeartbeatMissingNotification($minutesSince));
            Cache::put(self::ALERT_THROTTLE_KEY, true, now()->addMinutes(self::ALERT_THROTTLE_MINUTES));
            $this->warn("Alert sent — heartbeat missing for {$minutesSince} min.");
        } else {
            $this->error('Admin user not found, cannot send alert.');
        }
    }
}
