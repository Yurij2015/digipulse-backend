<?php

namespace App\Console\Commands;

use App\Infrastructure\Monitoring\Redis\MonitorHeartbeatProbe;
use App\Models\User;
use App\Notifications\MonitorHeartbeatMissingNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('app:check-monitor-heartbeat')]
#[Description('Alert admin only when the Go monitor fails Redis, HTTP, and check activity probes.')]
class CheckMonitorHeartbeat extends Command
{
    private const string ALERT_THROTTLE_KEY = 'go_monitor:heartbeat_alert_sent';

    public function __construct(private readonly MonitorHeartbeatProbe $heartbeatProbe)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $alertAfterMinutes = (int) config('monitoring.heartbeat.alert_after_minutes', 5);
        $alertThrottleMinutes = (int) config('monitoring.heartbeat.alert_throttle_minutes', 30);

        if ($this->heartbeatProbe->isOperational($alertAfterMinutes)) {
            Cache::forget(self::ALERT_THROTTLE_KEY);

            return;
        }

        if (Cache::has(self::ALERT_THROTTLE_KEY)) {
            $this->line('Monitor down but alert already sent recently — skipping.');

            return;
        }

        $minutesSince = $this->heartbeatProbe->minutesSinceLastBeat($alertAfterMinutes);

        $this->heartbeatProbe->logDiagnostics();

        Cache::put(self::ALERT_THROTTLE_KEY, true, now()->addMinutes($alertThrottleMinutes));

        $admin = User::where('email_bindex', User::generateBlindIndex(config('app.admin_email')))->first();

        if ($admin) {
            $admin->notify(new MonitorHeartbeatMissingNotification($minutesSince));
            $this->warn("Alert sent — monitor failed all liveness checks for {$minutesSince} min.");
        } else {
            $this->error('Admin user not found, cannot send alert.');
        }
    }
}
