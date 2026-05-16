<?php

namespace App\Infrastructure\Monitoring\Mappers;

use App\Domain\Monitoring\Models\Project as DomainProject;
use App\Models\Project as EloquentProject;
use Illuminate\Support\Carbon;

final readonly class EloquentProjectMapper
{
    public function __construct(
        private EloquentSiteMapper $siteMapper
    ) {}

    public function toDomain(EloquentProject $project): DomainProject
    {
        return new DomainProject(
            id: $project->id,
            userId: $project->user_id,
            name: $project->name,
            description: $project->description,
            sitesCount: (int) ($project->sites_count ?? $project->sites()->count()),
            sites: $project->relationLoaded('sites')
                ? $project->sites->map(fn ($site) => $this->siteMapper->toDomain($site))->all()
                : [],
            createdAt: $this->formatDate($project->created_at),
            updatedAt: $this->formatDate($project->updated_at),
        );
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date instanceof Carbon) {
            return $date->toIso8601String();
        }

        return is_string($date) ? $date : null;
    }
}
