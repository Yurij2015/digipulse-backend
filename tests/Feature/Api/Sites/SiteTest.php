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

    $response = $this->getJson(route('v1.sites.index'), [
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

    $response = $this->postJson(route('v1.sites.store'), $siteData, [
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
    $this->getJson(route('v1.sites.index'), [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(401);

    $this->postJson(route('v1.sites.store'), [], [
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

    $response = $this->postJson(route('v1.sites.store'), $siteData, [
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

    $response = $this->getJson(route('v1.check-types.index'), [
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

    $this->getJson(route('v1.sites.index'), [
        'X-Frontend-Key' => 'invalid-key',
    ])->assertStatus(401);
});

test('authenticated user can view their own site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.sites.show', $site), ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(200)
        ->assertJsonPath('data.id', $site->id);
});

test('authenticated user cannot view another user\'s site', function () {
    $user = User::factory()->create();
    $otherSite = Site::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson(route('v1.sites.show', $otherSite), ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(404);
});

test('authenticated user can update their site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    Sanctum::actingAs($user);

    $this->putJson(route('v1.sites.update', $site), ['name' => 'New Name'], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseHas('sites', ['id' => $site->id, 'name' => 'New Name']);
});

test('authenticated user cannot update another user\'s site', function () {
    $user = User::factory()->create();
    $otherSite = Site::factory()->create();

    Sanctum::actingAs($user);

    $this->putJson(route('v1.sites.update', $otherSite), ['name' => 'Hacked'], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(404);
});

test('authenticated user can delete their site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->deleteJson(route('v1.sites.destroy', $site), [], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(204);

    $this->assertDatabaseMissing('sites', ['id' => $site->id]);
});

test('authenticated user cannot delete another user\'s site', function () {
    $user = User::factory()->create();
    $otherSite = Site::factory()->create();

    Sanctum::actingAs($user);

    $this->deleteJson(route('v1.sites.destroy', $otherSite), [], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(404);

    $this->assertDatabaseHas('sites', ['id' => $otherSite->id]);
});

test('regular user cannot create more than 6 sites', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Site::factory()->count(6)->create(['user_id' => $user->id]);

    $this->postJson(route('v1.sites.store'), [
        'name' => 'Fourth Site',
        'url' => 'https://fourth.example.com',
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(403);
});

test('unverified user cannot create a site', function () {
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson(route('v1.sites.store'), [
        'name' => 'My Site',
        'url' => 'https://unverified.example.com',
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(403);
});

test('cannot create a site with a duplicate url', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Site::factory()->create(['url' => 'https://example.com']);

    $this->postJson(route('v1.sites.store'), [
        'name' => 'Duplicate',
        'url' => 'https://example.com',
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['url']);
});
