<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteStatsRepositoryInterface;
use App\Domain\Monitoring\Data\CreateSiteData;
use App\Domain\Monitoring\Models\Configuration as DomainConfiguration;
use App\Domain\Monitoring\Models\Site as DomainSite;
use App\Infrastructure\Monitoring\Mappers\EloquentConfigurationMapper;
use App\Infrastructure\Monitoring\Mappers\EloquentSiteMapper;
use App\Models\Site as EloquentSite;
use App\Models\SiteCheckConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

readonly class EloquentSiteRepository implements SiteManagementRepositoryInterface, SiteRepositoryInterface
{
    private const string CONFIG_CACHE_VERSION = 'v1';

    private const int CONFIG_CACHE_TTL = 3600;

    public function __construct(
        private EloquentSiteMapper $mapper,
        private EloquentConfigurationMapper $configurationMapper,
        private SiteStatsRepositoryInterface $statsRepository,
    ) {}

    public function findById(int $id): ?DomainSite
    {
        $site = EloquentSite::with([
            'latestCheck',
            'latestHttpCheck',
            'latestSslCheck',
            'latestPingCheck',
        ])->find($id);

        if (! $site) {
            return null;
        }

        return $this->mapper->toDomain(
            $site,
            $this->statsRepository->loadForSite($site->id, $site->update_interval),
            $this->getCachedConfigurations($site->id),
        );
    }

    public function findByUser(int $userId, ?int $projectId = null): array
    {
        $query = EloquentSite::where('user_id', $userId)
            ->with([
                'latestCheck',
                'latestHttpCheck',
                'latestSslCheck',
                'latestPingCheck',
            ]);

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $sites = $query->latest()->get();

        $siteIntervals = $sites->pluck('update_interval', 'id')->toArray();
        $statsBySiteId = $this->statsRepository->loadForSites($siteIntervals);

        return $sites
            ->map(fn (EloquentSite $site) => $this->mapper->toDomain(
                $site,
                $statsBySiteId[$site->id] ?? null,
                $this->getCachedConfigurations($site->id),
            ))
            ->toArray();
    }

    public function findIdsByUser(int $userId, ?int $projectId = null, ?int $siteId = null): array
    {
        return EloquentSite::where('user_id', $userId)
            ->when($projectId !== null, fn ($q) => $q->where('project_id', $projectId))
            ->when($siteId !== null, fn ($q) => $q->where('id', $siteId))
            ->pluck('id')
            ->toArray();
    }

    public function countByUser(int $userId): int
    {
        return EloquentSite::where('user_id', $userId)->count();
    }

    public function create(CreateSiteData $dto): DomainSite
    {
        $site = EloquentSite::create([
            'user_id' => $dto->userId,
            'project_id' => $dto->projectId,
            'name' => $dto->name,
            'url' => $dto->url,
            'update_interval' => $dto->updateInterval,
            'is_active' => $dto->isActive,
        ]);

        $this->clearConfigurationsCache($site->id);

        return $this->mapper->toDomain($site, configurations: $this->getCachedConfigurations($site->id));
    }

    public function update(int $id, array $data): DomainSite
    {
        $site = EloquentSite::findOrFail($id);
        $site->update($data);
        $site->load(['latestCheck', 'latestHttpCheck', 'latestSslCheck', 'latestPingCheck']);

        return $this->mapper->toDomain(
            $site,
            $this->statsRepository->loadForSite($site->id, $site->update_interval),
            $this->getCachedConfigurations($site->id),
        );
    }

    /**
     * @throws \Throwable
     */
    public function syncConfigurations(int $siteId, array $configurations): void
    {
        DB::transaction(static function () use ($siteId, $configurations) {
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

            // Deactivate removed configurations instead of deleting to preserve check_results history
            $site->configurations()
                ->whereNotIn('id', $updatedIds)
                ->update(['is_active' => false]);
        });

        $this->clearConfigurationsCache($siteId);
    }

    public function delete(int $id): bool
    {
        $this->clearConfigurationsCache($id);

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

    /** @return DomainConfiguration[] */
    private function getCachedConfigurations(int $siteId): array
    {
        $version = self::CONFIG_CACHE_VERSION;
        $cached = Cache::remember("site_{$siteId}_configurations_{$version}", self::CONFIG_CACHE_TTL, function () use ($siteId) {
            return SiteCheckConfiguration::where('site_id', $siteId)
                ->where('is_active', true)
                ->with('checkType')
                ->get()
                ->map(fn (SiteCheckConfiguration $c) => $this->configurationMapper->toDomain($c)->toArray())
                ->toArray();
        });

        return array_map(fn (array $data) => $this->configurationMapper->arrayToDomain($data), $cached);
    }

    private function clearConfigurationsCache(int $siteId): void
    {
        $version = self::CONFIG_CACHE_VERSION;
        Cache::forget("site_{$siteId}_configurations_{$version}");
    }
}
