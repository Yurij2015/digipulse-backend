<?php

use App\Domain\Monitoring\Contracts\SiteStatsRepositoryInterface;
use App\Models\CheckResult;
use App\Models\CheckType;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use App\Models\User;
use Database\Seeders\CheckTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CheckTypeSeeder::class);
    Cache::flush();
    $this->repository = app(SiteStatsRepositoryInterface::class);
});

it('returns empty array for empty input', function () {
    expect($this->repository->loadForSites([]))->toBe([]);
});

it('returns SiteStats keyed by site id for multiple sites', function () {
    $user = User::factory()->create();
    $site1 = Site::factory()->create(['user_id' => $user->id]);
    $site2 = Site::factory()->create(['user_id' => $user->id]);

    $result = $this->repository->loadForSites([
        $site1->id => $site1->update_interval,
        $site2->id => $site2->update_interval,
    ]);

    expect($result)->toHaveKeys([$site1->id, $site2->id]);
});

it('reports 100% uptime for a site with no check results', function () {
    $site = Site::factory()->create();

    $stats = $this->repository->loadForSites([$site->id => $site->update_interval]);

    expect($stats[$site->id]->uptime)->toBe(100.0);
});

it('computes uptime correctly from check results', function () {
    $site = Site::factory()->create();
    $config = SiteCheckConfiguration::factory()->create(['site_id' => $site->id]);

    CheckResult::factory()->count(3)->create([
        'site_id' => $site->id,
        'configuration_id' => $config->id,
        'status' => 'up',
        'checked_at' => now()->subHours(1),
    ]);
    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $config->id,
        'status' => 'down',
        'checked_at' => now()->subMinutes(30),
    ]);

    $stats = $this->repository->loadForSites([$site->id => $site->update_interval]);

    expect($stats[$site->id]->uptime)->toBe(75.0);
});

it('stores results in cache after first load', function () {
    $site = Site::factory()->create();
    $httpType = CheckType::where('slug', 'http')->firstOrFail();
    $config = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $httpType->id,
    ]);
    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $config->id,
        'status' => 'up',
        'response_time_ms' => 150,
        'checked_at' => now(),
    ]);

    $this->repository->loadForSites([$site->id => $site->update_interval]);

    // Cache::has() returns false for null values, so p95 requires at least one 'up' result
    expect(Cache::has("site_{$site->id}_uptime_v1"))->toBeTrue();
    expect(Cache::has("site_{$site->id}_rt_history_v1"))->toBeTrue();
    expect(Cache::has("site_{$site->id}_daily_history_v1"))->toBeTrue();
    expect(Cache::has("site_{$site->id}_apdex_v1"))->toBeTrue();
    expect(Cache::has("site_{$site->id}_p95_v1"))->toBeTrue();
});

it('returns cached values on second call', function () {
    $site = Site::factory()->create();
    $config = SiteCheckConfiguration::factory()->create(['site_id' => $site->id]);

    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $config->id,
        'status' => 'up',
        'checked_at' => now(),
    ]);

    $first = $this->repository->loadForSites([$site->id => $site->update_interval]);

    // Add a new result — if cache works, second call should still return old value
    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $config->id,
        'status' => 'down',
        'checked_at' => now(),
    ]);

    $second = $this->repository->loadForSites([$site->id => $site->update_interval]);

    expect($second[$site->id]->uptime)->toBe($first[$site->id]->uptime);
});

it('clearCache removes all five cache keys for a site', function () {
    $site = Site::factory()->create();

    $this->repository->loadForSites([$site->id => $site->update_interval]);
    $this->repository->clearCache($site->id);

    expect(Cache::has("site_{$site->id}_uptime_v1"))->toBeFalse();
    expect(Cache::has("site_{$site->id}_rt_history_v1"))->toBeFalse();
    expect(Cache::has("site_{$site->id}_daily_history_v1"))->toBeFalse();
    expect(Cache::has("site_{$site->id}_apdex_v1"))->toBeFalse();
    expect(Cache::has("site_{$site->id}_p95_v1"))->toBeFalse();
});

it('responseTimeHistory includes only http check results', function () {
    $site = Site::factory()->create();
    $httpType = CheckType::where('slug', 'http')->firstOrFail();
    $sslType = CheckType::where('slug', 'ssl')->firstOrFail();

    $httpConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $httpType->id,
    ]);
    $sslConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $site->id,
        'check_type_id' => $sslType->id,
    ]);

    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $httpConfig->id,
        'status' => 'up',
        'response_time_ms' => 200,
        'checked_at' => now()->subMinutes(2),
    ]);
    // SSL result with different response_time — must NOT appear in responseTimeHistory
    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $sslConfig->id,
        'status' => 'up',
        'response_time_ms' => 9999,
        'checked_at' => now()->subMinutes(1),
    ]);

    $stats = $this->repository->loadForSites([$site->id => $site->update_interval]);

    expect($stats[$site->id]->responseTimeHistory)->toBe([200])
        ->and($stats[$site->id]->responseTimeHistory)->not->toContain(9999);
});

it('apdex score is 1.0 when no check results exist', function () {
    $site = Site::factory()->create();

    $stats = $this->repository->loadForSites([$site->id => $site->update_interval]);

    expect($stats[$site->id]->apdexScore)->toBe(1.0);
});

it('p95 response time is null when no check results exist', function () {
    $site = Site::factory()->create();

    $stats = $this->repository->loadForSites([$site->id => $site->update_interval]);

    expect($stats[$site->id]->p95ResponseTime)->toBeNull();
});