<?php

use App\Models\Site;
use App\Models\CheckType;
use App\Models\SiteCheckConfiguration;
use App\Models\Check;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Run seeders to have check types available
    $this->seed(\Database\Seeders\CheckTypeSeeder::class);
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

    $check = Check::factory()->create([
        'site_check_configuration_id' => $config->id,
        'is_successful' => true,
        'response_time' => 250,
    ]);

    expect($config->refresh()->checks)->toHaveCount(1);
    expect($config->checks->first()->is_successful)->toBeTrue();
    expect($config->checks->first()->response_time)->toBe(250);
});
