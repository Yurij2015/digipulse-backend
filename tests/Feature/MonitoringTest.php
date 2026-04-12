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
