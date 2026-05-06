<?php

use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
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

    expect(CheckResult::query()->count())->toBe(0);
});

it('requeues payload when processing fails before max attempts', function () {
    $configuration = SiteCheckConfiguration::factory()->create();

    $payload = json_encode([
        'configuration_id' => $configuration->id,
        'status' => 'up',
        '_attempt' => 1,
    ], JSON_THROW_ON_ERROR);

    Redis::shouldReceive('brpop')
        ->once()
        ->with(['monitoring:results'], 5)
        ->andReturn(['monitoring:results', $payload]);

    Redis::shouldReceive('lpush')
        ->once()
        ->with('monitoring:results', Mockery::on(static fn ($value) => is_string($value) && str_contains($value, '"_attempt":2')));

    $siteRepository = Mockery::mock(SiteRepositoryInterface::class);
    $siteRepository->shouldReceive('getConfigurationContext')->once()->andThrow(new RuntimeException('boom'));
    app()->instance(SiteRepositoryInterface::class, $siteRepository);

    $this->artisan('app:consume-monitor-results', ['--once' => true])
        ->assertExitCode(0);
});

it('moves payload to failed queue when max attempts reached', function () {
    $configuration = SiteCheckConfiguration::factory()->create();

    config(['monitoring.results_consumer.max_attempts' => 2]);

    $payload = json_encode([
        'configuration_id' => $configuration->id,
        'status' => 'down',
        '_attempt' => 2,
    ], JSON_THROW_ON_ERROR);

    Redis::shouldReceive('brpop')
        ->once()
        ->with(['monitoring:results'], 5)
        ->andReturn(['monitoring:results', $payload]);

    Redis::shouldReceive('lpush')
        ->once()
        ->with('monitoring:results:failed', Mockery::on(static fn ($value) => is_string($value) && str_contains($value, '"_attempt":3')));

    $siteRepository = Mockery::mock(SiteRepositoryInterface::class);
    $siteRepository->shouldReceive('getConfigurationContext')->once()->andThrow(new RuntimeException('boom'));
    app()->instance(SiteRepositoryInterface::class, $siteRepository);

    $this->artisan('app:consume-monitor-results', ['--once' => true])
        ->assertExitCode(0);
});
