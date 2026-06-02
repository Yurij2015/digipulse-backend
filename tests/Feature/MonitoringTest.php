<?php

use App\Models\CheckResult;
use App\Models\CheckType;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use App\Models\User;
use Database\Seeders\CheckTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run seeders to have check types available
    $this->seed(CheckTypeSeeder::class);
});

it('can create a site with check configurations', function () {
    $user = User::factory()->create();
    $httpType = CheckType::where('slug', 'http')->first();

    $site = Site::factory()->create([
        'user_id' => $user->id,
    ]);

    $config = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $httpType->id,
        'params' => ['url' => $site->url],
    ]);

    expect($site->configurations)->toHaveCount(1);
    expect($site->configurations->first()->checkType->slug)->toBe('http');
});

it('can record a check result', function () {
    $config = SiteCheckConfiguration::factory()->create();

    $check = CheckResult::create([
        'site_id' => $config->site_id,
        'configuration_id' => $config->id,
        'status' => 'up',
        'response_time_ms' => 250,
        'checked_at' => now(),
    ]);

    expect($config->refresh()->results)->toHaveCount(1);
    expect($config->results->first()->status)->toBe('up');
    expect($config->results->first()->response_time_ms)->toBe(250);
});

it('latestHttpCheck returns the most recent http check and ignores other types', function () {
    $httpType = CheckType::where('slug', 'http')->firstOrFail();
    $sslType = CheckType::where('slug', 'ssl')->firstOrFail();

    $site = Site::factory()->create();
    $httpConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $httpType->id,
    ]);
    $sslConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $sslType->id,
    ]);

    $olderHttp = CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $httpConfig->id,
        'status' => 'down',
        'checked_at' => now()->subMinutes(10),
    ]);
    $latestHttp = CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $httpConfig->id,
        'status' => 'up',
        'checked_at' => now()->subMinutes(1),
    ]);
    // Newer than the HTTP check — must not bleed into latestHttpCheck
    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $sslConfig->id,
        'status' => 'up',
        'checked_at' => now(),
    ]);

    $site->load(['latestHttpCheck', 'latestSslCheck']);

    expect($site->latestHttpCheck->id)->toBe($latestHttp->id);
    expect($site->latestSslCheck->configuration_id)->toBe($sslConfig->id);
});

it('latestHttpCheck is null when only non-http check results exist', function () {
    $sslType = CheckType::where('slug', 'ssl')->firstOrFail();

    $site = Site::factory()->create();
    $sslConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $sslType->id,
    ]);
    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $sslConfig->id,
        'status' => 'up',
        'checked_at' => now(),
    ]);

    $site->load('latestHttpCheck');

    expect($site->latestHttpCheck)->toBeNull();
});

it('latestPingCheck returns the most recent ping check result', function () {
    $pingType = CheckType::where('slug', 'ping')->firstOrFail();
    $httpType = CheckType::where('slug', 'http')->firstOrFail();

    $site = Site::factory()->create();
    $pingConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $pingType->id,
    ]);
    $httpConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $httpType->id,
    ]);

    $latestPing = CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $pingConfig->id,
        'status' => 'up',
        'checked_at' => now()->subMinutes(1),
    ]);
    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $httpConfig->id,
        'status' => 'up',
        'checked_at' => now(),
    ]);

    $site->load('latestPingCheck');

    expect($site->latestPingCheck->id)->toBe($latestPing->id);
});
