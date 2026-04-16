<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Notifications\Notifiable;

class Site extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'url', 'update_interval', 'is_active'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(SiteCheckConfiguration::class);
    }

    public function checks(): HasManyThrough
    {
        return $this->hasManyThrough(CheckResult::class, SiteCheckConfiguration::class, 'site_id', 'configuration_id', 'id', 'id');
    }

    /**
     * Get the response time of the latest check.
     */
    public function getResponseTimeAttribute(): int
    {
        return (int) ($this->checks()
            ->latest('checked_at')
            ->first()?->response_time_ms ?? 0);
    }

    /**
     * Get uptime percentage for the last 30 days.
     */
    public function getUptimeAttribute(): float
    {
        $total = $this->checks()
            ->where('checked_at', '>=', now()->subDays(30))
            ->count();

        if ($total === 0) {
            return 0;
        }

        $up = $this->checks()
            ->where('checked_at', '>=', now()->subDays(30))
            ->where('status', 'up')
            ->count();

        return round(($up / $total) * 100, 2);
    }

    /**
     * Get the last check timestamp.
     */
    public function getLastCheckedAtAttribute(): ?string
    {
        return $this->checks()->latest('checked_at')->first()?->checked_at?->toIso8601String();
    }
}
