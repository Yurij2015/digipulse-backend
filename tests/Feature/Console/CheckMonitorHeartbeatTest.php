<?php

use App\Models\CheckResult;
use App\Models\SiteCheckConfiguration;
use App\Models\User;
use App\Notifications\MonitorHeartbeatMissingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

it('does not alert when heartbeat is missing but check results are recent', function () {
    Notification::fake();

    $config = SiteCheckConfiguration::factory()->create(['is_active' => true]);

    Redis::del('go_monitor:last_heartbeat');

    CheckResult::factory()->create([
        'site_id' => $config->site_id,
        'configuration_id' => $config->id,
        'checked_at' => now(),
    ]);

    Artisan::call('app:check-monitor-heartbeat');

    Notification::assertNothingSent();
});

it('does not alert when heartbeat is missing but monitor http health responds', function () {
    Notification::fake();

    SiteCheckConfiguration::factory()->create(['is_active' => true]);

    Http::fake([
        'http://digipulse-monitor:8080/health' => Http::response('OK', 200),
    ]);

    Redis::del('go_monitor:last_heartbeat');

    Artisan::call('app:check-monitor-heartbeat');

    Notification::assertNothingSent();
});

it('logs redis heartbeat read details when monitor is not operational', function () {
    Log::spy();

    SiteCheckConfiguration::factory()->create(['is_active' => true]);

    Http::fake([
        'http://digipulse-monitor:8080/health' => Http::response('Service Unavailable', 503),
    ]);

    Redis::del('go_monitor:last_heartbeat');

    Artisan::call('app:check-monitor-heartbeat');

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Go monitor liveness probe (not operational)'
                && $context['go_monitor']['redis_read']['logical_key'] === 'go_monitor:last_heartbeat'
                && $context['go_monitor']['redis_read']['redis_prefix'] === 'laravel-database-'
                && $context['go_monitor']['redis_read']['prefixed_key'] === 'laravel-database-go_monitor:last_heartbeat'
                && $context['go_monitor']['redis_read']['prefixed_value'] === null
                && $context['go_monitor']['http_health']['status'] === 503
                && $context['go_monitor']['check_results']['recent'] === false;
        });
});

it('alerts admin when heartbeat, http health, and check results are all absent', function () {
    Notification::fake();
    Log::spy();
    Cache::forget('go_monitor:heartbeat_alert_sent');

    SiteCheckConfiguration::factory()->create(['is_active' => true]);

    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'notify_email' => true,
    ]);

    Http::fake([
        'http://digipulse-monitor:8080/health' => Http::response('Service Unavailable', 503),
    ]);

    Redis::del('go_monitor:last_heartbeat');

    Artisan::call('app:check-monitor-heartbeat');

    Notification::assertSentTo($admin, MonitorHeartbeatMissingNotification::class);
});

it('clears throttle key when monitor recovers', function () {
    Cache::put('go_monitor:heartbeat_alert_sent', true, now()->addMinutes(30));

    Redis::set('go_monitor:last_heartbeat', (string) time());

    Artisan::call('app:check-monitor-heartbeat');

    expect(Cache::has('go_monitor:heartbeat_alert_sent'))->toBeFalse();
});

it('alerts admin even when no active configurations exist', function () {
    Notification::fake();
    Cache::forget('go_monitor:heartbeat_alert_sent');

    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'notify_email' => true,
    ]);

    Http::fake([
        'http://digipulse-monitor:8080/health' => Http::response('Service Unavailable', 503),
    ]);

    Redis::del('go_monitor:last_heartbeat');

    Artisan::call('app:check-monitor-heartbeat');

    Notification::assertSentTo($admin, MonitorHeartbeatMissingNotification::class);
});
