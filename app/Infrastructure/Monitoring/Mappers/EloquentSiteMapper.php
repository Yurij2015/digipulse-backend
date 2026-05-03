<?php

namespace App\Infrastructure\Monitoring\Mappers;

use App\Domain\Monitoring\Models\Site as DomainSite;
use App\Models\Site as EloquentSite;
use App\Models\SiteCheckConfiguration as EloquentConfiguration;
use Illuminate\Support\Carbon;

final class EloquentSiteMapper
{
    public function __construct(
        private EloquentConfigurationMapper $configurationMapper
    ) {}

    public function toDomain(EloquentSite $site): DomainSite
    {
        return new DomainSite(
            id: $site->id,
            userId: $site->user_id,
            name: $site->name,
            url: $site->url,
            updateInterval: $site->update_interval,
            isActive: (bool) $site->is_active,
            configurations: $site->relationLoaded('configurations')
                ? $site->configurations->map(fn (EloquentConfiguration $configuration) => $this->configurationMapper->toDomain($configuration))->all()
                : [],
            uptime: $site->uptime,
            responseTime: $site->response_time,
            lastCheckedAt: $this->formatDate($site->last_checked_at),
            serverInfo: $site->server_info,
            sslInfo: $site->ssl_info,
            pingInfo: $site->ping_info,
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
            serverInfo: $data['server_info'] ?? null,
            sslInfo: $data['ssl_info'] ?? null,
            pingInfo: $data['ping_info'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof Carbon) {
            return $date->toIso8601String();
        }

        return is_string($date) ? $date : null;
    }
}
