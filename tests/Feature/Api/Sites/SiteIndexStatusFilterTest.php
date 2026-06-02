<?php

use App\Models\CheckResult;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use App\Models\User;
use Database\Seeders\CheckTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CheckTypeSeeder::class);
    Cache::flush();
    $this->frontendKey = config('app.frontend_key');
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

/**
 * Creates a site for the current user with a given latest check status.
 * Pass null $status to leave the site with no check results (pending).
 */
function siteWithLatestStatus(User $user, ?string $status): Site
{
    $site = Site::factory()->create(['user_id' => $user->id]);

    if ($status !== null) {
        $config = SiteCheckConfiguration::factory()->create(['site_id' => $site->id]);
        CheckResult::factory()->create([
            'site_id' => $site->id,
            'configuration_id' => $config->id,
            'status' => $status,
            'checked_at' => now(),
        ]);
    }

    return $site;
}

test('status=up returns only sites whose latest check is up', function () {
    $up = siteWithLatestStatus($this->user, 'up');
    siteWithLatestStatus($this->user, 'down');
    siteWithLatestStatus($this->user, null); // pending

    $response = $this->getJson(
        route('v1.sites.index', ['status' => 'up']),
        ['X-Frontend-Key' => $this->frontendKey],
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $up->id)
        ->assertJsonPath('meta.total', 1);
});

test('status=down returns only sites whose latest check is down', function () {
    siteWithLatestStatus($this->user, 'up');
    $down = siteWithLatestStatus($this->user, 'down');
    siteWithLatestStatus($this->user, null);

    $response = $this->getJson(
        route('v1.sites.index', ['status' => 'down']),
        ['X-Frontend-Key' => $this->frontendKey],
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $down->id)
        ->assertJsonPath('meta.total', 1);
});

test('status=pending returns only sites with no check results', function () {
    siteWithLatestStatus($this->user, 'up');
    siteWithLatestStatus($this->user, 'down');
    $pending = siteWithLatestStatus($this->user, null);

    $response = $this->getJson(
        route('v1.sites.index', ['status' => 'pending']),
        ['X-Frontend-Key' => $this->frontendKey],
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id)
        ->assertJsonPath('meta.total', 1);
});

test('status=slow returns only sites whose latest check is slow', function () {
    siteWithLatestStatus($this->user, 'up');
    $slow = siteWithLatestStatus($this->user, 'slow');

    $response = $this->getJson(
        route('v1.sites.index', ['status' => 'slow']),
        ['X-Frontend-Key' => $this->frontendKey],
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $slow->id);
});

test('invalid status value is ignored and returns all sites', function () {
    siteWithLatestStatus($this->user, 'up');
    siteWithLatestStatus($this->user, 'down');
    siteWithLatestStatus($this->user, null);

    $response = $this->getJson(
        route('v1.sites.index', ['status' => 'invalid_value']),
        ['X-Frontend-Key' => $this->frontendKey],
    );

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 3);
});

test('status filter only returns sites owned by the authenticated user', function () {
    $otherUser = User::factory()->create();
    siteWithLatestStatus($this->user, 'up');
    siteWithLatestStatus($otherUser, 'up'); // belongs to another user

    $response = $this->getJson(
        route('v1.sites.index', ['status' => 'up']),
        ['X-Frontend-Key' => $this->frontendKey],
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 1);
});

test('meta.total reflects filtered count not total count', function () {
    siteWithLatestStatus($this->user, 'up');
    siteWithLatestStatus($this->user, 'up');
    siteWithLatestStatus($this->user, 'down');

    $response = $this->getJson(
        route('v1.sites.index', ['status' => 'up']),
        ['X-Frontend-Key' => $this->frontendKey],
    );

    $response->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonCount(2, 'data');
});