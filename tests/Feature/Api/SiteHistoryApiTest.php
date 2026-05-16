<?php

use App\Models\CheckResult;
use App\Models\CheckResultArchive;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    $this->config = SiteCheckConfiguration::factory()->create(['site_id' => $this->site->id]);
});

it('returns aggregated live stats for the current week', function () {
    $now = Carbon::now()->startOfWeek()->addDay(); // Tuesday
    Carbon::setTestNow($now);

    // Create results in different hours
    CheckResult::factory()->create([
        'site_id' => $this->site->id,
        'configuration_id' => $this->config->id,
        'status' => 'up',
        'response_time_ms' => 100,
        'checked_at' => $now->copy()->subHours(2),
    ]);

    CheckResult::factory()->create([
        'site_id' => $this->site->id,
        'configuration_id' => $this->config->id,
        'status' => 'up',
        'response_time_ms' => 200,
        'checked_at' => $now->copy()->subHours(2)->addMinutes(10),
    ]);

    CheckResult::factory()->create([
        'site_id' => $this->site->id,
        'configuration_id' => $this->config->id,
        'status' => 'down',
        'error_message' => 'Connection timeout',
        'checked_at' => $now->copy()->subHours(1),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('v1.sites.history', [
            'site' => $this->site->id,
            'week' => $now->format('Y-\WW'),
        ]), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'stats',
                'incidents',
            ],
        ]);

    $stats = $response->json('data.stats');
    $incidents = $response->json('data.incidents');

    expect($stats)->toHaveCount(2); // Two different hours
    expect($incidents)->toHaveCount(1); // One down event
    expect($incidents[0]['error_message'])->toBe('Connection timeout');
});

it('returns aggregated stats from archived data for past weeks', function () {
    $pastWeek = Carbon::now()->subWeeks(2);
    $year = (int) $pastWeek->format('o');
    $week = (int) $pastWeek->format('W');

    CheckResultArchive::create([
        'site_id' => $this->site->id,
        'configuration_id' => $this->config->id,
        'year' => $year,
        'week' => $week,
        'data' => [
            [
                'status' => 'up',
                'response_time_ms' => 150,
                'checked_at' => $pastWeek->copy()->startOfDay()->addHours(10)->toDateTimeString(),
            ],
            [
                'status' => 'down',
                'response_time_ms' => 0,
                'error_message' => '500 Internal Server Error',
                'checked_at' => $pastWeek->copy()->startOfDay()->addHours(11)->toDateTimeString(),
            ],
        ],
        'size_bytes' => 1024,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('v1.sites.history', [
            'site' => $this->site->id,
            'week' => $pastWeek->format('Y-\WW'),
        ]), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ]);

    $response->assertStatus(200);

    $stats = $response->json('data.stats');
    $incidents = $response->json('data.incidents');

    expect($stats)->toHaveCount(2);
    expect($incidents)->toHaveCount(1);
    expect($incidents[0]['error_message'])->toBe('500 Internal Server Error');
});

it('prevents another user from accessing site history', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->getJson(route('v1.sites.history', ['site' => $this->site->id]), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ])
        ->assertStatus(404);
});

it('returns latest_results for each active configuration', function () {
    $now = Carbon::now();

    CheckResult::factory()->create([
        'site_id' => $this->site->id,
        'configuration_id' => $this->config->id,
        'status' => 'up',
        'checked_at' => $now,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('v1.sites.history', ['site' => $this->site->id]), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['latest_results']]);

    $latestResults = $response->json('data.latest_results');
    expect($latestResults)->toHaveCount(1);
    expect($latestResults[0]['config_id'])->toBe($this->config->id);
    expect($latestResults[0]['is_active'])->toBeTrue();
    expect($latestResults[0]['result'])->not->toBeNull();
});

it('includes disabled configurations in latest_results with last known result', function () {
    $now = Carbon::now();

    $disabledConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $this->site->id,
        'is_active' => false,
    ]);

    CheckResult::factory()->create([
        'site_id' => $this->site->id,
        'configuration_id' => $disabledConfig->id,
        'status' => 'up',
        'checked_at' => $now->copy()->subDays(3),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('v1.sites.history', ['site' => $this->site->id]), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ]);

    $response->assertStatus(200);

    $latestResults = $response->json('data.latest_results');
    $disabledEntry = collect($latestResults)->firstWhere('config_id', $disabledConfig->id);

    expect($disabledEntry)->not->toBeNull();
    expect($disabledEntry['is_active'])->toBeFalse();
    expect($disabledEntry['result'])->not->toBeNull();
    expect($disabledEntry['result']['status'])->toBe('up');
});

it('includes disabled configurations with null result when they never ran', function () {
    $disabledConfig = SiteCheckConfiguration::factory()->create([
        'site_id' => $this->site->id,
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('v1.sites.history', ['site' => $this->site->id]), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ]);

    $response->assertStatus(200);

    $latestResults = $response->json('data.latest_results');
    $disabledEntry = collect($latestResults)->firstWhere('config_id', $disabledConfig->id);

    expect($disabledEntry)->not->toBeNull();
    expect($disabledEntry['is_active'])->toBeFalse();
    expect($disabledEntry['result'])->toBeNull();
});

it('rejects an invalid week format', function () {
    $this->actingAs($this->user)
        ->getJson(route('v1.sites.history', ['site' => $this->site->id, 'week' => 'bad-format']), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['week']);
});

it('filters history by configuration_id', function () {
    $otherConfig = SiteCheckConfiguration::factory()->create(['site_id' => $this->site->id]);
    $now = Carbon::now();

    CheckResult::factory()->create([
        'site_id' => $this->site->id,
        'configuration_id' => $this->config->id,
        'checked_at' => $now,
    ]);

    CheckResult::factory()->create([
        'site_id' => $this->site->id,
        'configuration_id' => $otherConfig->id,
        'checked_at' => $now,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('v1.sites.history', [
            'site' => $this->site->id,
            'configuration_id' => $this->config->id,
        ]), [
            'X-Frontend-Key' => config('app.frontend_key'),
        ]);

    $response->assertStatus(200);
    $stats = $response->json('data.stats');

    // Should only have data points for the first config
    // Actually, stats are aggregated by hour across ALL matching configs in the query.
    // If we filter by config_id, it should only count checks for THAT config.
    // In our test, both have checks in the same hour.
    // Total count for filtered config should be 1.
    expect($stats[0]['count'])->toBe(1);
});
