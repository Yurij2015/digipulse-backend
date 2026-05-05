<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Site extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'url', 'update_interval', 'is_active'];

    protected $appends = [
        'response_time',
        'uptime',
        'last_checked_at',
        'server_info',
        'ssl_info',
        'ping_info',
        'response_time_history',
        'daily_uptime_history',
        'apdex_score',
        'p95_response_time',
    ];

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

    /**
     * Get the last 12 response times for sparkline.
     */
    public function getResponseTimeHistoryAttribute(): array
    {
        return $this->checks()
            ->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'http'))
            ->latest('checked_at')
            ->limit(12)
            ->get()
            ->reverse()
            ->map(fn ($check) => $check->response_time_ms)
            ->values()
            ->toArray();
    }

    /**
     * Get the last 30 days of uptime for heatmap.
     */
    public function getDailyUptimeHistoryAttribute(): array
    {
        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            $total = $this->checks()
                ->whereBetween('checked_at', [$date, $endOfDay])
                ->count();

            $up = $this->checks()
                ->whereBetween('checked_at', [$date, $endOfDay])
                ->where('status', 'up')
                ->count();

            // Include archives if needed
            $archives = CheckResultArchive::where('site_id', $this->id)
                ->where('year', $date->year)
                ->where('week', $date->weekOfYear)
                ->get();

            foreach ($archives as $archive) {
                foreach ($archive->data as $result) {
                    $checkedAt = Carbon::parse($result['checked_at']);
                    if ($checkedAt->between($date, $endOfDay)) {
                        $total++;
                        if ($result['status'] === 'up') {
                            $up++;
                        }
                    }
                }
            }

            $percentage = $total > 0 ? round(($up / $total) * 100, 2) : 100;

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'uptime' => $percentage,
                'total_checks' => $total,
            ];
        }

        return $days;
    }

    /**
     * Calculate Apdex score for the last 30 days.
     * T = 300ms, 4T = 1200ms
     */
    public function getApdexScoreAttribute(): float
    {
        return Cache::remember("site_{$this->id}_apdex_v1", 600, function () {
            $since = now()->subDays(30);
            
            $stats = $this->checks()
                ->where('checked_at', '>=', $since)
                ->where('status', 'up')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN response_time_ms <= 300 THEN 1 ELSE 0 END) as satisfied,
                    SUM(CASE WHEN response_time_ms > 300 AND response_time_ms <= 1200 THEN 1 ELSE 0 END) as tolerating
                ')
                ->first();

            $total = $this->checks()->where('checked_at', '>=', $since)->count();
            if ($total === 0) return 1.0;

            $satisfied = (int) ($stats->satisfied ?? 0);
            $tolerating = (int) ($stats->tolerating ?? 0);

            return round(($satisfied + ($tolerating / 2)) / $total, 2);
        });
    }

    /**
     * Calculate P95 response time for the last 30 days.
     */
    public function getP95ResponseTimeAttribute(): ?int
    {
        return Cache::remember("site_{$this->id}_p95_v1", 600, function () {
            $since = now()->subDays(30);
            
            // Using a more efficient way to get P95
            $times = $this->checks()
                ->where('checked_at', '>=', $since)
                ->where('status', 'up')
                ->whereNotNull('response_time_ms')
                ->orderBy('response_time_ms')
                ->pluck('response_time_ms');

            if ($times->isEmpty()) return null;

            $count = $times->count();
            $index = max(0, (int)ceil(0.95 * $count) - 1);
            
            return (int) $times[$index];
        });
    }
}
