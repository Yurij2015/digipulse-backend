<?php

namespace App\Domain\Monitoring\Models;

readonly class Project
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public ?string $description = null,
        public int $sitesCount = 0,
        /** @var Site[] */
        public array $sites = [],
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'description' => $this->description,
            'sites_count' => $this->sitesCount,
            'sites' => array_map(static fn (Site $site) => $site->toArray(), $this->sites),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
