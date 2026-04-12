<?php

namespace Tests\Feature\Api\Webhooks;

use App\Models\SiteCheckConfiguration;
use App\Models\CheckType;
use Database\Seeders\CheckTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CheckTypeSeeder::class);
    // Set up the internal monitor key for tests
    Config::set('app.internal_monitor_key', 'test-monitor-key');
});

it('can receive and process monitoring results via webhook', function () {
    $config = SiteCheckConfiguration::factory()->create();

    $payload = [
        'configuration_id' => $config->id,
        'status' => 'up',
        'response_time_ms' => 123,
        'metadata' => ['test' => 'data'],
    ];

    $response = $this->postJson(route('webhooks.results'), $payload, [
        'X-Monitor-Key' => 'test-monitor-key',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('check_results', [
        'configuration_id' => $config->id,
        'status' => 'up',
        'response_time_ms' => 123,
    ]);

    expect($config->refresh()->last_status)->toBe('up');
});

it('rejects webhook requests with invalid key', function () {
    $config = SiteCheckConfiguration::factory()->create();

    $response = $this->postJson(route('webhooks.results'), [
        'configuration_id' => $config->id,
        'status' => 'up',
    ], [
        'X-Monitor-Key' => 'wrong-key',
    ]);

    $response->assertStatus(401);
});
