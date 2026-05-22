<?php

namespace App\Http\Resources;

use App\Domain\Monitoring\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Project',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 42),
        new OA\Property(property: 'name', type: 'string', example: 'Client Alpha'),
        new OA\Property(property: 'description', type: 'string', example: 'All sites for Client Alpha', nullable: true),
        new OA\Property(property: 'sites_count', type: 'integer', example: 5),
        new OA\Property(property: 'sites', type: 'array', items: new OA\Items(ref: '#/components/schemas/SiteSchema')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
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
