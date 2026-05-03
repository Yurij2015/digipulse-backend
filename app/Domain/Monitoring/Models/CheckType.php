<?php

namespace App\Domain\Monitoring\Models;

readonly class CheckType
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description = null,
        public ?string $icon = null,
        public bool $isActive = true,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'is_active' => $this->isActive,
        ];
    }
}
