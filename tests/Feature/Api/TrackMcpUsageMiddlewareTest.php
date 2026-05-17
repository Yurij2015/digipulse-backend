<?php

use App\Models\McpTokenUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->headers = ['X-Frontend-Key' => config('app.frontend_key')];
});

describe('TrackMcpUsage middleware', function () {
    it('records usage when mcp endpoint called with token auth', function () {
        $plainText = $this->user->createToken('mcp-token', ['mcp'])->plainTextToken;

        $this->getJson(route('v1.mcp.overview'), array_merge($this->headers, [
            'Authorization' => "Bearer {$plainText}",
        ]));

        expect(McpTokenUsage::count())->toBe(1);

        $row = McpTokenUsage::first();
        expect($row->user_id)->toBe($this->user->id)
            ->and($row->endpoint)->toBe('v1.mcp.overview')
            ->and($row->count)->toBe(1);
    });

    it('increments count on repeated requests', function () {
        $plainText = $this->user->createToken('mcp-token', ['mcp'])->plainTextToken;
        $authHeaders = array_merge($this->headers, ['Authorization' => "Bearer {$plainText}"]);

        $this->getJson(route('v1.mcp.overview'), $authHeaders);
        $this->getJson(route('v1.mcp.overview'), $authHeaders);
        $this->getJson(route('v1.mcp.overview'), $authHeaders);

        expect(McpTokenUsage::count())->toBe(1)
            ->and(McpTokenUsage::first()->count)->toBe(3);
    });

    it('creates separate rows per endpoint', function () {
        $plainText = $this->user->createToken('mcp-token', ['mcp'])->plainTextToken;
        $authHeaders = array_merge($this->headers, ['Authorization' => "Bearer {$plainText}"]);

        $this->getJson(route('v1.mcp.overview'), $authHeaders);
        $this->getJson(route('v1.mcp.incidents'), $authHeaders);

        expect(McpTokenUsage::count())->toBe(2);
    });

    it('does not record usage when using session auth', function () {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);

        $this->getJson(route('v1.mcp.overview'), $this->headers);

        expect(McpTokenUsage::count())->toBe(0);
    });

    it('does not record usage on non-mcp routes', function () {
        $plainText = $this->user->createToken('mcp-token', ['mcp'])->plainTextToken;

        $this->getJson(route('v1.tokens.index'), array_merge($this->headers, [
            'Authorization' => "Bearer {$plainText}",
        ]));

        expect(McpTokenUsage::count())->toBe(0);
    });
});
