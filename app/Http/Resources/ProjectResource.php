<?php

namespace App\Http\Resources;

use App\Domain\Monitoring\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Project $resource
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->userId,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'sites_count' => $this->resource->sitesCount,
            'sites' => SiteResource::collection($this->resource->sites),
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
