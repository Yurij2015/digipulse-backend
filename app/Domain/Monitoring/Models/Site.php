<?php

namespace App\Domain\Monitoring\Models;

readonly class Site
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public string $url,
        public int $updateInterval,
        public bool $isActive,
        /** @var Configuration[] */
        public array $configurations = [],
        public ?float $uptime = null,
        public ?int $responseTime = null,
        public ?string $lastCheckedAt = null,
        public ?array $serverInfo = null,
        public ?array $sslInfo = null,
        public ?array $pingInfo = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'url' => $this->url,
            'update_interval' => $this->updateInterval,
            'is_active' => $this->isActive,
            'configurations' => array_map(fn (Configuration $config) => $config->toArray(), $this->configurations),
            'uptime' => $this->uptime,
            'response_time' => $this->responseTime,
            'last_checked_at' => $this->lastCheckedAt,
            'server_info' => $this->serverInfo,
            'ssl_info' => $this->sslInfo,
            'ping_info' => $this->pingInfo,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
