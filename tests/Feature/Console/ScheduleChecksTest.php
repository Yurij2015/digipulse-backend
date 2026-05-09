<?php

use App\Infrastructure\Monitoring\OutboundInternetProbe;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

it('does not enqueue checks when the outbound internet probe fails', function () {
    Http::fake(fn () => throw new ConnectionException('Simulated connection failure'));

    $config = SiteCheckConfiguration::factory()->create([
        'last_checked_at' => null,
    ]);

    Artisan::call('app:schedule-checks');

    expect($config->fresh()->last_checked_at)->toBeNull();
});

it('treats the network as reachable when the internet check is disabled', function () {
    config(['monitoring.scheduler.internet_check_enabled' => false]);
    Http::fake(fn () => Http::response('', 503));

    $probe = app(OutboundInternetProbe::class);

    expect($probe->isReachable())->toBeTrue();
});

it('enqueues due checks and updates last_checked_at', function () {
    Http::fake(['*' => Http::response('', 200)]);

    $config = SiteCheckConfiguration::factory()->create(['last_checked_at' => null]);

    Redis::shouldReceive('lpush')
        ->once()
        ->with('monitoring:tasks', Mockery::on(
            fn ($payload) => is_string($payload)
                && str_contains($payload, '"configuration_id":'.$config->id)
        ));

    Artisan::call('app:schedule-checks');

    expect($config->fresh()->last_checked_at)->not->toBeNull();
});

it('skips already-checked configs that are not yet due', function () {
    Http::fake(['*' => Http::response('', 200)]);

    Redis::shouldReceive('lpush')->never();

    SiteCheckConfiguration::factory()->create([
        'last_checked_at' => now()->subSeconds(10),
        'site_id' => Site::factory()->create(['update_interval' => 300])->id,
    ]);

    Artisan::call('app:schedule-checks');
});
