<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'check_type' => $this->checkType ? [
                'id' => $this->checkType->id,
                'name' => $this->checkType->name,
                'slug' => $this->checkType->slug,
                'description' => $this->checkType->description,
                'icon' => $this->checkType->icon,
                'is_active' => $this->checkType->isActive,
            ] : null,
            'params' => $this->params,
            'is_active' => $this->isActive,
            'last_status' => $this->lastStatus,
            'last_checked_at' => $this->lastCheckedAt,
        ];
    }
}
