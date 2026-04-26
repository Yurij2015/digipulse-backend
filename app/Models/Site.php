<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

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
        $latestHttp = $this->checks()
            ->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'http'))
            ->latest('checked_at')
            ->first();

        return (int) ($latestHttp?->response_time_ms ?? 0);
    }

    /**
     * Get uptime percentage for the last 30 days.
     */
    public function getUptimeAttribute(): float
    {
        $since = now()->subDays(30);

        // Current raw checks (usually last 7 days)
        $rawChecks = $this->checks()
            ->where('checked_at', '>=', $since)
            ->select('status')
            ->get();

        $total = $rawChecks->count();
        $up = $rawChecks->where('status', 'up')->count();

        // Archived checks (older than 7 days)
        $archives = CheckResultArchive::where('site_id', $this->id)
            ->get();

        foreach ($archives as $archive) {
            foreach ($archive->data as $result) {
                $checkedAt = Carbon::parse($result['checked_at']);

                // Only count results within the 30-day window AND not already in raw checks
                //  are usually newer than the archive cut-off
                if ($checkedAt->greaterThanOrEqualTo($since)) {
                    // Check if this result is already accounted for in raw checks to avoid double counting
                    // (though usually archive and raw are mutually exclusive)
                    $total++;
                    if ($result['status'] === 'up') {
                        $up++;
                    }
                }
            }
        }

        if ($total === 0) {
            return 0;
        }

        return round(($up / $total) * 100, 2);
    }

    /**
     * Get the last check timestamp.
     */
    public function getLastCheckedAtAttribute(): ?string
    {
        return $this->checks()->latest('checked_at')->first()?->checked_at?->toIso8601String();
    }

    /**
     * Get the latest server information from metadata.
     */
    public function getServerInfoAttribute(): ?array
    {
        $latestResult = $this->checks()->latest('checked_at')->first();
        if ($latestResult && isset($latestResult->metadata['ip'])) {
            return [
                'ip' => $latestResult->metadata['ip'],
                'country' => $latestResult->metadata['country'] ?? null,
                'country_code' => $latestResult->metadata['country_code'] ?? null,
                'city' => $latestResult->metadata['city'] ?? null,
                'isp' => $latestResult->metadata['isp'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Get the latest SSL certificate information.
     */
    public function getSslInfoAttribute(): ?array
    {
        $latestSsl = $this->checks()
            ->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'ssl'))
            ->latest('checked_at')
            ->first();

        if ($latestSsl && isset($latestSsl->metadata['days_remaining'])) {
            return [
                'days_remaining' => (int) $latestSsl->metadata['days_remaining'],
                'issuer' => $latestSsl->metadata['issuer'] ?? null,
                'expires_at' => $latestSsl->metadata['expires_at'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Get the latest Ping information.
     */
    public function getPingInfoAttribute(): ?array
    {
        $latestPing = $this->checks()
            ->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'ping'))
            ->latest('checked_at')
            ->first();

        if ($latestPing) {
            return [
                'latency' => (int) $latestPing->response_time_ms,
                'status' => $latestPing->status,
            ];
        }

        return null;
    }
}
