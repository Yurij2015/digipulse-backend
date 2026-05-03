<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function checks(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    /**
     * Get the latest check result for the site.
     */
    public function latestCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->latestOfMany('checked_at');
    }

    /**
     * Get the latest HTTP check result for the site.
     */
    public function latestHttpCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->ofMany([
            'checked_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'http'));
        });
    }

    /**
     * Get the response time of the latest check.
     */
    public function getResponseTimeAttribute(): ?int
    {
        if ($this->relationLoaded('latestHttpCheck')) {
            return $this->latestHttpCheck?->response_time_ms;
        }

        if (isset($this->latest_response_time)) {
            return (int) $this->latest_response_time;
        }

        return $this->latestHttpCheck?->response_time_ms;
    }

    /**
     * Get uptime percentage for the last 30 days.
     */
    public function getUptimeAttribute(): float
    {
        $since = now()->subDays(30);

        if (isset($this->checks_total_count) && isset($this->checks_up_count)) {
            $total = (int) $this->checks_total_count;
            $up = (int) $this->checks_up_count;
        } else {
            $total = $this->checks()->where('checked_at', '>=', $since)->count();
            $up = $this->checks()->where('checked_at', '>=', $since)->where('status', 'up')->count();
        }

        // Archived checks (older than 7 days)
        $archives = CheckResultArchive::where('site_id', $this->id)
            ->get();

        foreach ($archives as $archive) {
            foreach ($archive->data as $result) {
                $checkedAt = Carbon::parse($result['checked_at']);

                if ($checkedAt->greaterThanOrEqualTo($since)) {
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
        if ($this->relationLoaded('latestCheck')) {
            return $this->latestCheck?->checked_at?->toIso8601String();
        }

        if (isset($this->last_checked_at_timestamp)) {
            return Carbon::parse($this->last_checked_at_timestamp)->toIso8601String();
        }

        return $this->latestCheck?->checked_at?->toIso8601String();
    }

    /**
     * Get the latest server information from metadata.
     */
    public function getServerInfoAttribute(): ?array
    {
        $latestResult = $this->relationLoaded('latestCheck') ? $this->latestCheck : $this->latestCheck()->first();

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
