<?php

use App\Models\CheckResult;
use App\Models\SiteCheckConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

it('processes one monitor result message from redis', function () {
    $configuration = SiteCheckConfiguration::factory()->create([
        'last_status' => null,
    ]);

    $payload = json_encode([
        'configuration_id' => $configuration->id,
        'status' => 'up',
        'response_time_ms' => 87,
        'metadata' => ['probe' => 'cloudflare'],
    ], JSON_THROW_ON_ERROR);

    Redis::shouldReceive('brpop')
        ->once()
        ->with(['monitoring:results'], 5)
        ->andReturn(['monitoring:results', $payload]);

    $this->artisan('app:consume-monitor-results', ['--once' => true])
        ->assertExitCode(0);

    expect(CheckResult::query()->where('configuration_id', $configuration->id)->exists())->toBeTrue();
    expect($configuration->fresh()->last_status)->toBe('up');
});

it('skips invalid monitor result payloads', function () {
    $payload = json_encode([
        'configuration_id' => 999999,
        'status' => 'invalid-status',
    ], JSON_THROW_ON_ERROR);

    Redis::shouldReceive('brpop')
        ->once()
        ->with(['monitoring:results'], 5)
        ->andReturn(['monitoring:results', $payload]);

    $this->artisan('app:consume-monitor-results', ['--once' => true])
        ->assertExitCode(0);

    expect(CheckResult::count())->toBe(0);
});
