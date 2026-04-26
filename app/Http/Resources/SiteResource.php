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
            'update_interval' => $this->update_interval,
            'is_active' => $this->is_active,
            'response_time' => $this->response_time,
            'uptime' => $this->uptime,
            'last_checked_at' => $this->last_checked_at,
            'server_info' => $this->server_info,
            'ssl_info' => $this->ssl_info,
            'ping_info' => $this->ping_info,
            'configurations' => ConfigurationResource::collection($this->whenLoaded('configurations')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
