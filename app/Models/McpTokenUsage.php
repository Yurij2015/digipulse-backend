<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class McpTokenUsage extends Model
{
    protected $fillable = ['user_id', 'token_id', 'endpoint', 'date', 'count'];

    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    public static function track(int $userId, int $tokenId, string $endpoint): void
    {
        static::upsert(
            [
                'user_id' => $userId,
                'token_id' => $tokenId,
                'endpoint' => $endpoint,
                'date' => now()->toDateString(),
                'count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            uniqueBy: ['token_id', 'endpoint', 'date'],
            update: [
                'count' => DB::raw('mcp_token_usages.count + 1'),
                'updated_at' => now(),
            ]
        );
    }

    public static function totalsForTokens(array $tokenIds): Collection
    {
        return static::selectRaw('token_id, SUM(count) as total_requests, MAX(updated_at) as last_used_at')
            ->whereIn('token_id', $tokenIds)
            ->groupBy('token_id')
            ->get()
            ->keyBy('token_id');
    }
}
