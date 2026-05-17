<?php

namespace App\Services;

use App\Models\McpTokenUsage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TokenService
{
    private const int CACHE_TTL = 300;

    public function getTokensForUser(int $userId): array
    {
        return Cache::remember("user_tokens:{$userId}", self::CACHE_TTL, static function () use ($userId) {
            $tokens = User::find($userId)
                ->tokens()
                ->where('name', '!=', 'auth_token')
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'created_at', 'last_used_at']);

            $usages = McpTokenUsage::totalsForTokens($tokens->pluck('id')->all());

            return $tokens->map(function ($token) use ($usages) {
                $usage = $usages->get($token->id);

                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at?->toISOString(),
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'total_requests' => $usage ? (int) $usage->total_requests : 0,
                ];
            })->all();
        });
    }

    public function createToken(User $user, string $name): array
    {
        $newToken = $user->createToken($name, ['mcp']);

        $base = rtrim(config('app.mcp_server_url') ?: config('app.url'), '/');

        $this->invalidateCache($user->id);

        return [
            'id' => $newToken->accessToken->id,
            'name' => $newToken->accessToken->name,
            'mcp_url' => $base.'/mcp?token='.$newToken->plainTextToken,
            'created_at' => $newToken->accessToken->created_at?->toISOString(),
        ];
    }

    public function revokeToken(User $user, int $tokenId): bool
    {
        $deleted = $user->tokens()
            ->where('id', $tokenId)
            ->where('name', '!=', 'auth_token')
            ->delete();

        if ($deleted) {
            $this->invalidateCache($user->id);
        }

        return (bool) $deleted;
    }

    public function getTokenUsage(User $user, int $tokenId): ?array
    {
        $token = $user->tokens()
            ->where('id', $tokenId)
            ->where('name', '!=', 'auth_token')
            ->first();

        if (! $token) {
            return null;
        }

        $rows = McpTokenUsage::where('token_id', $tokenId)
            ->orderBy('date')
            ->get(['endpoint', 'date', 'count']);

        $byEndpoint = $rows->groupBy('endpoint')->map(fn ($g) => $g->sum('count'));
        $byDay = $rows->groupBy(fn ($r) => $r->date->toDateString())->map(fn ($g) => $g->sum('count'));

        return [
            'token_id' => $token->id,
            'token_name' => $token->name,
            'total_requests' => $rows->sum('count'),
            'by_endpoint' => $byEndpoint,
            'by_day' => $byDay,
        ];
    }

    private function invalidateCache(int $userId): void
    {
        Cache::forget("user_tokens:{$userId}");
    }
}
