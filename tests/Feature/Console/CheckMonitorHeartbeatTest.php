<?php

use App\Models\SiteCheckConfiguration;
use App\Models\User;
use App\Notifications\MonitorHeartbeatMissingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.admin_email' => 'admin@example.com',
        'database.redis.options.prefix' => 'laravel-database-',
    ]);
});

it('does not alert when go monitor heartbeat key matches laravel redis prefix', function () {
    Notification::fake();

    SiteCheckConfiguration::factory()->create(['is_active' => true]);

    Redis::set('go_monitor:last_heartbeat', (string) time());

    Artisan::call('app:check-monitor-heartbeat');

    Notification::assertNothingSent();
});

it('alerts admin when heartbeat is missing for active checks', function () {
    Notification::fake();
    Cache::forget('go_monitor:heartbeat_alert_sent');

    SiteCheckConfiguration::factory()->create(['is_active' => true]);

    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'notify_email' => true,
    ]);

    Redis::del('go_monitor:last_heartbeat');

    Artisan::call('app:check-monitor-heartbeat');

    Notification::assertSentTo($admin, MonitorHeartbeatMissingNotification::class);
});

it('skips heartbeat check when no active configurations exist', function () {
    Notification::fake();

    SiteCheckConfiguration::factory()->create(['is_active' => false]);

    Redis::del('go_monitor:last_heartbeat');

    Artisan::call('app:check-monitor-heartbeat');

    Notification::assertNothingSent();
});
