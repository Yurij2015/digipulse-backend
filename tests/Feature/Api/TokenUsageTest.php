<?php

use App\Models\McpTokenUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->headers = ['X-Frontend-Key' => config('app.frontend_key')];
});

describe('GET /tokens/{id}/usage', function () {
    it('returns usage breakdown for own token', function () {
        Sanctum::actingAs($this->user);
        $token = $this->user->createToken('My token', ['mcp'])->accessToken;

        McpTokenUsage::track($this->user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($this->user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($this->user->id, $token->id, 'v1.mcp.incidents');

        $this->getJson(route('v1.tokens.usage', $token->id), $this->headers)
            ->assertOk()
            ->assertJsonStructure(['token_id', 'token_name', 'total_requests', 'by_endpoint', 'by_day'])
            ->assertJsonPath('token_id', $token->id)
            ->assertJsonPath('token_name', 'My token')
            ->assertJsonPath('total_requests', 3)
            ->assertJsonPath('by_endpoint.v1.mcp.overview', 2)
            ->assertJsonPath('by_endpoint.v1.mcp.incidents', 1);
    });

    it('returns zero stats when no usage recorded', function () {
        Sanctum::actingAs($this->user);
        $token = $this->user->createToken('Empty token', ['mcp'])->accessToken;

        $this->getJson(route('v1.tokens.usage', $token->id), $this->headers)
            ->assertOk()
            ->assertJsonPath('total_requests', 0)
            ->assertJsonPath('by_endpoint', [])
            ->assertJsonPath('by_day', []);
    });

    it('returns 404 for non-existent token', function () {
        Sanctum::actingAs($this->user);

        $this->getJson(route('v1.tokens.usage', 9999), $this->headers)
            ->assertNotFound();
    });

    it('returns 404 for another user token', function () {
        $other = User::factory()->create();
        $otherToken = $other->createToken('Other', ['mcp'])->accessToken;

        Sanctum::actingAs($this->user);

        $this->getJson(route('v1.tokens.usage', $otherToken->id), $this->headers)
            ->assertNotFound();
    });

    it('returns 404 for auth_token', function () {
        Sanctum::actingAs($this->user);
        $authToken = $this->user->createToken('auth_token')->accessToken;

        $this->getJson(route('v1.tokens.usage', $authToken->id), $this->headers)
            ->assertNotFound();
    });

    it('groups usage by day correctly', function () {
        Sanctum::actingAs($this->user);
        $token = $this->user->createToken('My token', ['mcp'])->accessToken;

        \Carbon\Carbon::setTestNow('2026-05-15');
        McpTokenUsage::track($this->user->id, $token->id, 'v1.mcp.overview');

        \Carbon\Carbon::setTestNow('2026-05-17');
        McpTokenUsage::track($this->user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($this->user->id, $token->id, 'v1.mcp.overview');

        $response = $this->getJson(route('v1.tokens.usage', $token->id), $this->headers)
            ->assertOk();

        expect($response->json('by_day'))->toHaveKeys(['2026-05-15', '2026-05-17'])
            ->and($response->json('by_day.2026-05-15'))->toBe(1)
            ->and($response->json('by_day.2026-05-17'))->toBe(2);
    });

    it('requires authentication', function () {
        $this->getJson(route('v1.tokens.usage', 1), $this->headers)
            ->assertUnauthorized();
    });
});
