<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->frontendKey = config('app.frontend_key');
});

test('health endpoint returns check results with frontend key', function () {
    Redis::shouldReceive('ping')->andReturn(true);
    Redis::shouldReceive('get')->with('go_monitor:last_heartbeat')->andReturn((string) time());

    $this->getJson(route('v1.health'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', true)
        ->assertJsonPath('checks.redis', true)
        ->assertJsonPath('checks.go_monitor', true);
});

test('health endpoint returns degraded when go monitor heartbeat is stale', function () {
    Redis::shouldReceive('ping')->andReturn(true);
    Redis::shouldReceive('get')->with('go_monitor:last_heartbeat')->andReturn((string) (time() - 600));

    $this->getJson(route('v1.health'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertStatus(503)
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.go_monitor', false);
});

test('health endpoint requires valid frontend key', function () {
    $this->getJson(route('v1.health'), ['X-Frontend-Key' => 'wrong'])
        ->assertUnauthorized();
});
