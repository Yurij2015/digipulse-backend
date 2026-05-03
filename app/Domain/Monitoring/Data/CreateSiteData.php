<?php

namespace App\Domain\Monitoring\Data;

readonly class CreateSiteData
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $url,
        public int $updateInterval = 5,
        public bool $isActive = true,
    ) {}
}
