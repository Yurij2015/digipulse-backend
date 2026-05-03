<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
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
            'name' => $this->name,
            'url' => $this->url,
            'update_interval' => $this->updateInterval,
            'is_active' => $this->isActive,
            'response_time' => $this->responseTime,
            'uptime' => $this->uptime,
            'last_checked_at' => $this->lastCheckedAt,
            'server_info' => $this->serverInfo,
            'ssl_info' => $this->sslInfo,
            'ping_info' => $this->pingInfo,
            'configurations' => ConfigurationResource::collection($this->configurations ?? [])->resolve(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
