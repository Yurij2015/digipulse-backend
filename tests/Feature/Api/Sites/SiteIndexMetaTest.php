<?php

use App\Domain\Monitoring\Contracts\CachePortInterface;
use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Models\CheckResult;
use App\Models\Project;
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
    $this->frontendKey = config('app.frontend_key');
});

test('sites index includes status_counts in meta', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);
    $config = SiteCheckConfiguration::factory()->create(['site_id' => $site->id]);

    CheckResult::factory()->create([
        'site_id' => $site->id,
        'configuration_id' => $config->id,
        'status' => 'down',
        'checked_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.sites.index'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertOk()
        ->assertJsonPath('meta.status_counts.down', 1)
        ->assertJsonPath('meta.status_counts.up', 0)
        ->assertJsonPath('meta.status_counts.pending', 0)
        ->assertJsonPath('meta.total', 1);
});

test('sites without check results count as pending in status_counts', function () {
    $user = User::factory()->create();
    Site::factory()->count(2)->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.sites.index'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertOk()
        ->assertJsonPath('meta.status_counts.pending', 2)
        ->assertJsonPath('meta.status_counts.down', 0)
        ->assertJsonPath('meta.total', 2);
});

test('sites index total and status_counts refresh after creating a site', function () {
    $user = User::factory()->create();
    Site::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.sites.index'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertJsonPath('meta.total', 1);

    $this->postJson(route('v1.sites.store'), [
        'name' => 'Second Site',
        'url' => 'https://second.example.com',
        'update_interval' => 600,
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertCreated();

    $this->getJson(route('v1.sites.index'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.status_counts.pending', 2);
});

test('sites index respects project_id filter for total and status_counts', function () {
    $user = User::factory()->create();
    $projectA = Project::factory()->create(['user_id' => $user->id]);
    $projectB = Project::factory()->create(['user_id' => $user->id]);

    Site::factory()->create(['user_id' => $user->id, 'project_id' => $projectA->id]);
    Site::factory()->create(['user_id' => $user->id, 'project_id' => $projectB->id]);
    Site::factory()->create(['user_id' => $user->id, 'project_id' => null]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.sites.index', ['project_id' => $projectA->id]), [
        'X-Frontend-Key' => $this->frontendKey,
    ])
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.status_counts.pending', 1);
});

test('deleting a site invalidates cached site index totals', function () {
    $user = User::factory()->create();
    $keep = Site::factory()->create(['user_id' => $user->id]);
    $remove = Site::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.sites.index'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertJsonPath('meta.total', 2);

    $this->deleteJson(route('v1.sites.destroy', $remove), [], [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertNoContent();

    $this->getJson(route('v1.sites.index'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertJsonPath('meta.total', 1)
        ->assertJsonCount(1, 'data');
});

test('cached count is bypassed after clearUserSitesCache generation bump', function () {
    Cache::flush();

    $user = User::factory()->create();
    $repository = app(SiteManagementRepositoryInterface::class);

    Site::factory()->create(['user_id' => $user->id]);

    expect($repository->countByFilter($user->id, null))->toBe(1);

    Site::factory()->create(['user_id' => $user->id]);

    // Still cached until invalidation
    expect($repository->countByFilter($user->id, null))->toBe(1);

    app(CachePortInterface::class)->clearUserSitesCache($user->id);

    expect($repository->countByFilter($user->id, null))->toBe(2);
});
