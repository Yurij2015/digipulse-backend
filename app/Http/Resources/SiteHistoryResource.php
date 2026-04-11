<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SiteHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'stats' => $this->resource['stats'],
            'incidents' => CheckResultResource::collection($this->resource['incidents']),
        ];
    }
}
