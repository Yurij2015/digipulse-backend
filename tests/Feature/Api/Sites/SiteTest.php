<?php

use App\Models\CheckType;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->frontendKey = config('app.frontend_key');
});

test('authenticated user can list their sites', function () {
    $user = User::factory()->create();
    $checkType = CheckType::first() ?? CheckType::factory()->create();

    Site::factory()->count(2)->create(['user_id' => $user->id]);
    $siteWithConfiguration = Site::factory()->create(['user_id' => $user->id]);
    SiteCheckConfiguration::factory()->create([
        'site_id' => $siteWithConfiguration->id,
        'check_type_id' => $checkType->id,
    ]);

    // Another user's site
    Site::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson(route('sites.index'), [
        'X-Frontend-Key' => $this->frontendKey,
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');

    $sitePayload = collect($response->json('data'))->firstWhere('id', $siteWithConfiguration->id);

    expect($sitePayload)->not->toBeNull();
    expect($sitePayload['configurations'])->toHaveCount(1);
});

test('authenticated user can create a site', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $siteData = [
        'name' => 'Test Site',
        'url' => 'https://example.com',
        'update_interval' => 600,
    ];

    $response = $this->postJson(route('sites.store'), $siteData, [
        'X-Frontend-Key' => $this->frontendKey,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Test Site')
        ->assertJsonPath('data.url', 'https://example.com');

    $this->assertDatabaseHas('sites', [
        'user_id' => $user->id,
        'name' => 'Test Site',
        'url' => 'https://example.com',
        'update_interval' => 600,
    ]);
});

test('guest cannot list or create sites', function () {
    $this->getJson(route('sites.index'), [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(401);

    $this->postJson(route('sites.store'), [], [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(401);
});

test('authenticated user can create a site with checks', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $checkType = CheckType::first() ?? CheckType::factory()->create();

    $siteData = [
        'name' => 'Site with Checks',
        'url' => 'https://example.com',
        'update_interval' => 600,
        'checks' => [
            [
                'check_type_id' => $checkType->id,
                'params' => ['keyword' => 'test'],
            ],
        ],
    ];

    $response = $this->postJson(route('sites.store'), $siteData, [
        'X-Frontend-Key' => $this->frontendKey,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Site with Checks')
        ->assertJsonCount(1, 'data.configurations');

    $this->assertDatabaseHas('site_check_configurations', [
        'check_type_id' => $checkType->id,
    ]);

    $config = SiteCheckConfiguration::where('check_type_id', $checkType->id)->first();
    expect($config->params)->toBe(['keyword' => 'test']);
});

test('authenticated user can list available check types', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson(route('check-types.index'), [
        'X-Frontend-Key' => $this->frontendKey,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'description', 'icon', 'is_active'],
            ],
        ]);
});

test('request must have valid frontend key', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson(route('sites.index'), [
        'X-Frontend-Key' => 'invalid-key',
    ])->assertStatus(401);
});
