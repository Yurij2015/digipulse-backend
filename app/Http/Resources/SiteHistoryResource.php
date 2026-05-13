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
            'latest_results' => array_map(fn ($item) => [
                'config_id' => $item['config_id'],
                'type_name' => $item['type_name'],
                'type_slug' => $item['type_slug'],
                'is_active' => $item['is_active'],
                'result' => $item['result'] ? new CheckResultResource($item['result']) : null,
            ], $this->resource['latest_results'] ?? []),
        ];
    }
}
