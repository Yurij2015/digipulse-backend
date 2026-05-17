<?php

use App\Models\McpTokenUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('McpTokenUsage::track()', function () {
    it('creates a new row on first call', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['mcp'])->accessToken;

        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');

        expect(McpTokenUsage::count())->toBe(1);

        $row = McpTokenUsage::first();
        expect($row->user_id)->toBe($user->id)
            ->and($row->token_id)->toBe($token->id)
            ->and($row->endpoint)->toBe('v1.mcp.overview')
            ->and($row->date->toDateString())->toBe(today()->toDateString())
            ->and($row->count)->toBe(1);
    });

    it('increments count on repeated calls same day', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['mcp'])->accessToken;

        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');

        expect(McpTokenUsage::count())->toBe(1)
            ->and(McpTokenUsage::first()->count)->toBe(3);
    });

    it('creates separate rows for different endpoints', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['mcp'])->accessToken;

        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.incidents');

        expect(McpTokenUsage::count())->toBe(2);
    });

    it('creates separate rows for different days', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['mcp'])->accessToken;

        \Carbon\Carbon::setTestNow('2026-05-16');
        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');

        \Carbon\Carbon::setTestNow('2026-05-17');
        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');

        expect(McpTokenUsage::count())->toBe(2);
    });

    it('creates separate rows for different tokens', function () {
        $user = User::factory()->create();
        $tokenA = $user->createToken('A', ['mcp'])->accessToken;
        $tokenB = $user->createToken('B', ['mcp'])->accessToken;

        McpTokenUsage::track($user->id, $tokenA->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $tokenB->id, 'v1.mcp.overview');

        expect(McpTokenUsage::count())->toBe(2);
    });
});

describe('McpTokenUsage::totalsForTokens()', function () {
    it('returns aggregated totals keyed by token_id', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['mcp'])->accessToken;

        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $token->id, 'v1.mcp.incidents');

        $totals = McpTokenUsage::totalsForTokens([$token->id]);

        expect($totals)->toHaveKey($token->id)
            ->and((int) $totals[$token->id]->total_requests)->toBe(3);
    });

    it('returns empty collection for tokens with no usage', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['mcp'])->accessToken;

        $totals = McpTokenUsage::totalsForTokens([$token->id]);

        expect($totals)->toBeEmpty();
    });

    it('does not mix totals between tokens', function () {
        $user = User::factory()->create();
        $tokenA = $user->createToken('A', ['mcp'])->accessToken;
        $tokenB = $user->createToken('B', ['mcp'])->accessToken;

        McpTokenUsage::track($user->id, $tokenA->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $tokenA->id, 'v1.mcp.overview');
        McpTokenUsage::track($user->id, $tokenB->id, 'v1.mcp.overview');

        $totals = McpTokenUsage::totalsForTokens([$tokenA->id, $tokenB->id]);

        expect((int) $totals[$tokenA->id]->total_requests)->toBe(2)
            ->and((int) $totals[$tokenB->id]->total_requests)->toBe(1);
    });
});
