<?php

namespace App\Domain\Monitoring\Contracts;

use App\Domain\Monitoring\Models\Project;

interface ProjectRepositoryInterface
{
    public function findById(int $id): ?Project;

    /** @return Project[] */
    public function findByUser(int $userId): array;

    public function create(array $data): Project;

    public function update(int $id, array $data): Project;

    public function delete(int $id): bool;
}
