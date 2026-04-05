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
            'check_type' => new CheckTypeResource($this->whenLoaded('checkType')),
            'params' => $this->params,
            'is_active' => $this->is_active,
            'last_status' => $this->last_status,
            'last_checked_at' => $this->last_checked_at,
        ];
    }
}
