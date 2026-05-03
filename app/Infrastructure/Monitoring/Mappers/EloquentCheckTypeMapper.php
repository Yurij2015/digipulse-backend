<?php

namespace App\Infrastructure\Monitoring\Mappers;

use App\Domain\Monitoring\Models\CheckType as DomainCheckType;
use App\Models\CheckType as EloquentCheckType;

final class EloquentCheckTypeMapper
{
    public function toDomain(EloquentCheckType $checkType): DomainCheckType
    {
        return new DomainCheckType(
            id: $checkType->id,
            name: $checkType->name,
            slug: $checkType->slug,
            description: $checkType->description,
            icon: $checkType->icon,
            isActive: (bool) $checkType->is_active,
        );
    }

    public function arrayToDomain(array $data): DomainCheckType
    {
        return new DomainCheckType(
            id: $data['id'],
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            icon: $data['icon'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }
}
