<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\ProjectRepositoryInterface;
use App\Domain\Monitoring\Models\Project as DomainProject;
use App\Infrastructure\Monitoring\Mappers\EloquentProjectMapper;
use App\Models\Project as EloquentProject;

readonly class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private EloquentProjectMapper $mapper
    ) {}

    public function findById(int $id): ?DomainProject
    {
        $project = EloquentProject::withCount('sites')->find($id);

        return $project ? $this->mapper->toDomain($project) : null;
    }

    public function findByUser(int $userId): array
    {
        return EloquentProject::where('user_id', $userId)
            ->withCount('sites')
            ->latest()
            ->get()
            ->map(fn (EloquentProject $project) => $this->mapper->toDomain($project))
            ->toArray();
    }

    public function create(array $data): DomainProject
    {
        $project = EloquentProject::create($data);

        return $this->mapper->toDomain($project);
    }

    public function update(int $id, array $data): DomainProject
    {
        $project = EloquentProject::findOrFail($id);
        $project->update($data);

        return $this->mapper->toDomain($project);
    }

    public function delete(int $id): bool
    {
        return (bool) EloquentProject::where('id', $id)->delete();
    }
}
