<?php

namespace App\Infrastructure\Monitoring\Mappers;

use App\Domain\Monitoring\Models\Configuration as DomainConfiguration;
use App\Models\SiteCheckConfiguration as EloquentConfiguration;
use Illuminate\Support\Carbon;

final class EloquentConfigurationMapper
{
    public function __construct(
        private EloquentCheckTypeMapper $checkTypeMapper
    ) {}

    public function toDomain(EloquentConfiguration $configuration): DomainConfiguration
    {
        return new DomainConfiguration(
            id: $configuration->id,
            siteId: $configuration->site_id,
            checkTypeId: $configuration->check_type_id,
            isActive: (bool) $configuration->is_active,
            params: $configuration->params,
            lastStatus: $configuration->last_status,
            lastCheckedAt: $this->formatDate($configuration->last_checked_at),
            checkType: $configuration->relationLoaded('checkType') && $configuration->checkType
                ? $this->checkTypeMapper->toDomain($configuration->checkType)
                : null,
        );
    }

    public function arrayToDomain(array $data): DomainConfiguration
    {
        return new DomainConfiguration(
            id: $data['id'],
            siteId: $data['site_id'],
            checkTypeId: $data['check_type_id'],
            isActive: $data['is_active'],
            params: $data['params'] ?? null,
            lastStatus: $data['last_status'] ?? null,
            lastCheckedAt: $data['last_checked_at'] ?? null,
            checkType: isset($data['check_type']) ? $this->checkTypeMapper->arrayToDomain($data['check_type']) : null,
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
