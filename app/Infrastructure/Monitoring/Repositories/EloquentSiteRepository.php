<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
use App\Domain\Monitoring\Data\CreateSiteData;
use App\Domain\Monitoring\Models\Site as DomainSite;
use App\Infrastructure\Monitoring\Mappers\EloquentSiteMapper;
use App\Models\Site as EloquentSite;
use App\Models\SiteCheckConfiguration;

class EloquentSiteRepository implements SiteManagementRepositoryInterface, SiteRepositoryInterface
{
    public function __construct(
        private readonly EloquentSiteMapper $mapper,
    ) {}

    public function findById(int $id): ?DomainSite
    {
        $site = EloquentSite::with(['configurations.checkType'])->find($id);

        return $site ? $this->mapper->toDomain($site) : null;
    }

    public function findByUser(int $userId): array
    {
        return EloquentSite::where('user_id', $userId)
            ->with(['configurations.checkType'])
            ->latest()
            ->get()
            ->map(fn (EloquentSite $site) => $this->mapper->toDomain($site))
            ->toArray();
    }

    public function create(CreateSiteData $dto): DomainSite
    {
        $site = EloquentSite::create([
            'user_id' => $dto->userId,
            'name' => $dto->name,
            'url' => $dto->url,
            'update_interval' => $dto->updateInterval,
            'is_active' => $dto->isActive,
        ]);

        return $this->mapper->toDomain($site->load(['configurations.checkType']));
    }

    public function update(int $id, array $data): DomainSite
    {
        $site = EloquentSite::findOrFail($id);
        $site->update($data);

        return $this->mapper->toDomain($site->load(['configurations.checkType']));
    }

    public function syncConfigurations(int $siteId, array $configurations): void
    {
        $site = EloquentSite::findOrFail($siteId);
        $updatedIds = [];

        foreach ($configurations as $configData) {
            if (isset($configData['id'])) {
                $config = $site->configurations()->findOrFail($configData['id']);
                $config->update($configData);
            } else {
                $config = $site->configurations()->create($configData);
            }
            $updatedIds[] = $config->id;
        }

        $site->configurations()->whereNotIn('id', $updatedIds)->delete();
    }

    public function delete(int $id): bool
    {
        return (bool) EloquentSite::where('id', $id)->delete();
    }

    public function updateStatus(int $configurationId, string $status): void
    {
        SiteCheckConfiguration::where('id', $configurationId)->update([
            'last_status' => $status,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * @return array{site_id: int, user_id: int, last_status: ?string}
     */
    public function getConfigurationContext(int $configurationId): array
    {
        $row = SiteCheckConfiguration::join('sites', 'sites.id', '=', 'site_check_configurations.site_id')
            ->where('site_check_configurations.id', $configurationId)
            ->select([
                'site_check_configurations.site_id',
                'site_check_configurations.last_status',
                'sites.user_id',
            ])
            ->firstOrFail();

        return [
            'site_id' => (int) $row->site_id,
            'user_id' => (int) $row->user_id,
            'last_status' => $row->last_status,
        ];
    }

    public function fromArray(array $data): DomainSite
    {
        return $this->mapper->arrayToSite($data);
    }
}
