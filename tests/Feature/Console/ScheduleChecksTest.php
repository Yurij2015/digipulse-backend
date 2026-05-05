<?php

use App\Infrastructure\Monitoring\OutboundInternetProbe;
use App\Models\SiteCheckConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('does not enqueue checks when the outbound internet probe fails', function () {
    Http::fake(fn () => Http::response('', 503));

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
