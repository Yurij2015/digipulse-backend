<?php

namespace App\Infrastructure\Monitoring\Mappers;

use App\Domain\Monitoring\Data\SiteStats;
use App\Domain\Monitoring\Models\Site as DomainSite;
use App\Models\Site as EloquentSite;
use App\Models\SiteCheckConfiguration as EloquentConfiguration;
use Illuminate\Support\Carbon;

final class EloquentSiteMapper
{
    public function __construct(
        private readonly EloquentConfigurationMapper $configurationMapper
    ) {}

    public function toDomain(EloquentSite $site, ?SiteStats $stats = null, array $configurations = []): DomainSite
    {
        return new DomainSite(
            id: $site->id,
            userId: $site->user_id,
            name: $site->name,
            url: $site->url,
            updateInterval: $site->update_interval,
            isActive: (bool) $site->is_active,
            configurations: $this->resolveConfigurations($site, $configurations),
            uptime: $stats?->uptime,
            responseTime: $site->relationLoaded('latestHttpCheck')
                ? $site->latestHttpCheck?->response_time_ms
                : null,
            lastCheckedAt: $site->relationLoaded('latestCheck')
                ? $this->formatDate($site->latestCheck?->checked_at)
                : null,
            status: $site->relationLoaded('latestCheck') && $site->latestCheck
                ? $site->latestCheck->status
                : 'pending',
            serverInfo: $this->extractServerInfo($site),
            sslInfo: $this->extractSslInfo($site),
            pingInfo: $this->extractPingInfo($site),
            responseTimeHistory: $stats?->responseTimeHistory ?? [],
            dailyUptimeHistory: $stats?->dailyUptimeHistory ?? [],
            apdexScore: $stats?->apdexScore ?? 1.0,
            p95ResponseTime: $stats?->p95ResponseTime,
            createdAt: $this->formatDate($site->created_at),
            updatedAt: $this->formatDate($site->updated_at),
        );
    }

    public function arrayToSite(array $data): DomainSite
    {
        return new DomainSite(
            id: $data['id'],
            userId: $data['user_id'],
            name: $data['name'],
            url: $data['url'],
            updateInterval: $data['update_interval'],
            isActive: (bool) $data['is_active'],
            configurations: array_map(fn (array $config) => $this->configurationMapper->arrayToDomain($config), $data['configurations'] ?? []),
            uptime: isset($data['uptime']) ? (float) $data['uptime'] : null,
            responseTime: isset($data['response_time']) ? (int) $data['response_time'] : null,
            lastCheckedAt: $data['last_checked_at'] ?? null,
            status: $data['status'] ?? 'pending',
            serverInfo: $data['server_info'] ?? null,
            sslInfo: $data['ssl_info'] ?? null,
            pingInfo: $data['ping_info'] ?? null,
            responseTimeHistory: $data['response_time_history'] ?? [],
            dailyUptimeHistory: $data['daily_uptime_history'] ?? [],
            apdexScore: $data['apdex_score'] ?? 1.0,
            p95ResponseTime: $data['p95_response_time'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }

    private function resolveConfigurations(EloquentSite $site, array $configurations): array
    {
        if ($configurations !== []) {
            return $configurations;
        }

        if ($site->relationLoaded('configurations')) {
            return $site->configurations
                ->map(fn (EloquentConfiguration $c) => $this->configurationMapper->toDomain($c))
                ->all();
        }

        return [];
    }

    private function extractServerInfo(EloquentSite $site): ?array
    {
        if (! $site->relationLoaded('latestCheck')) {
            return null;
        }

        $latest = $site->latestCheck;
        if (! $latest || ! isset($latest->metadata['ip'])) {
            return null;
        }

        return [
            'ip' => $latest->metadata['ip'],
            'country' => $latest->metadata['country'] ?? null,
            'country_code' => $latest->metadata['country_code'] ?? null,
            'city' => $latest->metadata['city'] ?? null,
            'isp' => $latest->metadata['isp'] ?? null,
        ];
    }

    private function extractSslInfo(EloquentSite $site): ?array
    {
        if (! $site->relationLoaded('latestSslCheck')) {
            return null;
        }

        $latest = $site->latestSslCheck;
        if (! $latest || ! isset($latest->metadata['days_remaining'])) {
            return null;
        }

        return [
            'days_remaining' => (int) $latest->metadata['days_remaining'],
            'issuer' => $latest->metadata['issuer'] ?? null,
            'expires_at' => $latest->metadata['expires_at'] ?? null,
        ];
    }

    private function extractPingInfo(EloquentSite $site): ?array
    {
        if (! $site->relationLoaded('latestPingCheck')) {
            return null;
        }

        $latest = $site->latestPingCheck;
        if (! $latest) {
            return null;
        }

        return [
            'latency' => (int) $latest->response_time_ms,
            'status' => $latest->status,
        ];
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof Carbon) {
            return $date->toIso8601String();
        }

        return is_string($date) ? $date : null;
    }
}
