<?php

use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->frontendKey = config('app.frontend_key');
});

test('authenticated user can list their projects', function () {
    $user = User::factory()->create();
    Project::factory()->count(3)->create(['user_id' => $user->id]);

    // Another user's project (should NOT appear)
    Project::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.projects.index'), [
        'X-Frontend-Key' => $this->frontendKey,
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('guest cannot list projects', function () {
    $this->getJson(route('v1.projects.index'), [
        'X-Frontend-Key' => config('app.frontend_key'),
    ])->assertStatus(401);
});

test('authenticated user can create a project', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson(route('v1.projects.store'), [
        'name' => 'Client Alpha',
        'description' => 'All sites for Client Alpha',
    ], ['X-Frontend-Key' => $this->frontendKey]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Client Alpha')
        ->assertJsonPath('data.description', 'All sites for Client Alpha');

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'Client Alpha',
    ]);
});

test('project creation requires a name', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson(route('v1.projects.store'), [], [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(422)->assertJsonValidationErrors(['name']);
});

test('authenticated user can view their own project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.projects.show', $project->id), [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(200)
        ->assertJsonPath('data.id', $project->id);
});

test('authenticated user cannot view another user\'s project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson(route('v1.projects.show', $otherProject->id), [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(404);
});

test('authenticated user can delete their project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->deleteJson(route('v1.projects.destroy', $project->id), [], [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(204);

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

test('deleting a project nullifies site project_id but does not delete sites', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $site = Site::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

    Sanctum::actingAs($user);

    $this->deleteJson(route('v1.projects.destroy', $project->id), [], [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertStatus(204);

    $this->assertDatabaseHas('sites', ['id' => $site->id]);
    $this->assertDatabaseHas('sites', ['id' => $site->id, 'project_id' => null]);
});

test('authenticated user can create a site with a valid project_id', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $response = $this->postJson(route('v1.sites.store'), [
        'name' => 'Project Site',
        'url' => 'https://project-site.example.com',
        'project_id' => $project->id,
    ], ['X-Frontend-Key' => $this->frontendKey]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('sites', [
        'project_id' => $project->id,
    ]);
});

test('authenticated user cannot assign a site to another user\'s project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create(); // belongs to another user

    Sanctum::actingAs($user);

    $this->postJson(route('v1.sites.store'), [
        'name' => 'Unauthorized Site',
        'url' => 'https://unauthorized.example.com',
        'project_id' => $otherProject->id,
    ], ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['project_id']);
});
