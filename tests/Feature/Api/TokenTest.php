<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->headers = ['X-Frontend-Key' => config('app.frontend_key')];
});

describe('GET /tokens', function () {
    it('lists only mcp tokens, not auth tokens', function () {
        Sanctum::actingAs($this->user);
        $this->user->createToken('auth_token');
        $this->user->createToken('My Claude token', ['mcp']);

        $this->getJson(route('v1.tokens.index'), $this->headers)
            ->assertOk()
            ->assertJsonCount(1, 'tokens')
            ->assertJsonPath('tokens.0.name', 'My Claude token');
    });

    it('returns empty list when no mcp tokens exist', function () {
        Sanctum::actingAs($this->user);

        $this->getJson(route('v1.tokens.index'), $this->headers)
            ->assertOk()
            ->assertJsonCount(0, 'tokens');
    });

    it('returns token fields without plaintext', function () {
        Sanctum::actingAs($this->user);
        $this->user->createToken('Token A', ['mcp']);

        $this->getJson(route('v1.tokens.index'), $this->headers)
            ->assertOk()
            ->assertJsonStructure([
                'tokens' => [['id', 'name', 'created_at', 'last_used_at']],
            ]);
    });

    it('does not return tokens of other users', function () {
        $other = User::factory()->create();
        $other->createToken('Other token', ['mcp']);

        Sanctum::actingAs($this->user);

        $this->getJson(route('v1.tokens.index'), $this->headers)
            ->assertOk()
            ->assertJsonCount(0, 'tokens');
    });

    it('requires authentication', function () {
        $this->getJson(route('v1.tokens.index'), $this->headers)
            ->assertUnauthorized();
    });
});

describe('POST /tokens', function () {
    it('creates a token and returns mcp_url with plaintext', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('v1.tokens.store'), [
            'name' => 'My MCP token',
        ], $this->headers);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'name', 'mcp_url', 'created_at'])
            ->assertJsonPath('name', 'My MCP token');

        expect($response->json('mcp_url'))->toContain('/mcp?token=');
        expect($this->user->fresh()->tokens)->toHaveCount(1);
    });

    it('mcp_url contains the actual plaintext token', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('v1.tokens.store'), [
            'name' => 'Test',
        ], $this->headers)->assertCreated();

        $mcpUrl = $response->json('mcp_url');
        $token = parse_url($mcpUrl, PHP_URL_QUERY);
        parse_str($token, $params);

        expect($params)->toHaveKey('token');
        expect($params['token'])->not->toBeEmpty();
    });

    it('creates token with mcp ability', function () {
        Sanctum::actingAs($this->user);

        $this->postJson(route('v1.tokens.store'), ['name' => 'Test'], $this->headers)
            ->assertCreated();

        $token = $this->user->tokens()->first();
        expect($token->abilities)->toBe(['mcp']);
    });

    it('requires name', function () {
        Sanctum::actingAs($this->user);

        $this->postJson(route('v1.tokens.store'), [], $this->headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('name cannot exceed 64 characters', function () {
        Sanctum::actingAs($this->user);

        $this->postJson(route('v1.tokens.store'), [
            'name' => str_repeat('a', 65),
        ], $this->headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('requires authentication', function () {
        $this->postJson(route('v1.tokens.store'), ['name' => 'Test'], $this->headers)
            ->assertUnauthorized();
    });
});

describe('DELETE /tokens/{id}', function () {
    it('revokes own mcp token', function () {
        Sanctum::actingAs($this->user);
        $token = $this->user->createToken('My token', ['mcp'])->accessToken;

        $this->deleteJson(route('v1.tokens.destroy', $token->id), [], $this->headers)
            ->assertNoContent();

        expect($this->user->fresh()->tokens)->toHaveCount(0);
    });

    it('returns 404 for non-existent token', function () {
        Sanctum::actingAs($this->user);

        $this->deleteJson(route('v1.tokens.destroy', 9999), [], $this->headers)
            ->assertNotFound();
    });

    it('cannot revoke another user token', function () {
        $other = User::factory()->create();
        $otherToken = $other->createToken('Other token', ['mcp'])->accessToken;

        Sanctum::actingAs($this->user);

        $this->deleteJson(route('v1.tokens.destroy', $otherToken->id), [], $this->headers)
            ->assertNotFound();

        expect($other->fresh()->tokens)->toHaveCount(1);
    });

    it('cannot revoke auth_token', function () {
        Sanctum::actingAs($this->user);
        $authToken = $this->user->createToken('auth_token')->accessToken;

        $this->deleteJson(route('v1.tokens.destroy', $authToken->id), [], $this->headers)
            ->assertNotFound();

        expect($this->user->fresh()->tokens)->toHaveCount(1);
    });

    it('requires authentication', function () {
        $this->deleteJson(route('v1.tokens.destroy', 1), [], $this->headers)
            ->assertUnauthorized();
    });
});
