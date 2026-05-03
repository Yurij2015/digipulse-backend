<?php

namespace App\Domain\Monitoring\Models;

readonly class Configuration
{
    public function __construct(
        public int $id,
        public int $siteId,
        public int $checkTypeId,
        public bool $isActive,
        public ?array $params,
        public ?string $lastStatus,
        public ?string $lastCheckedAt,
        public ?CheckType $checkType = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->siteId,
            'check_type_id' => $this->checkTypeId,
            'is_active' => $this->isActive,
            'params' => $this->params,
            'last_status' => $this->lastStatus,
            'last_checked_at' => $this->lastCheckedAt,
            'check_type' => $this->checkType?->toArray(),
        ];
    }
}
