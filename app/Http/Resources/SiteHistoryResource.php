<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CheckResultResource;

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
                'result' => new CheckResultResource($item['result']),
            ], $this->resource['latest_results'] ?? []),
        ];
    }
}
