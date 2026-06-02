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

    private const int SITES_CACHE_TTL = 60;

    public function __construct(
        private EloquentSiteMapper $mapper,
        private EloquentConfigurationMapper $configurationMapper,
        private SiteStatsRepositoryInterface $statsRepository,
    ) {
    }

    public function findById(int $id): ?DomainSite
    {
        $site = EloquentSite::with([
            'latestCheck',
            'latestHttpCheck',
            'latestSslCheck',
            'latestPingCheck',
        ])->find($id);

        if (!$site) {
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
            ->map(fn(EloquentSite $site) => $this->mapper->toDomain(
                $site,
                $statsBySiteId[$site->id] ?? null,
                $this->getCachedConfigurations($site->id),
            ))
            ->toArray();
    }

    public function findIdsByUser(int $userId, ?int $projectId = null, ?int $siteId = null): array
    {
        return EloquentSite::where('user_id', $userId)
            ->when($projectId !== null, fn($q) => $q->where('project_id', $projectId))
            ->when($siteId !== null, fn($q) => $q->where('id', $siteId))
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

        return (bool)EloquentSite::where('id', $id)->delete();
    }

    public function updateStatus(
        int $configurationId,
        string $status,
        int $consecutiveFailures,
        ?\DateTimeInterface $confirmedDownAt,
    ): void {
        SiteCheckConfiguration::where('id', $configurationId)->update([
            'last_status' => $status,
            'last_checked_at' => now(),
            'consecutive_failures' => $consecutiveFailures,
            'confirmed_down_at' => $confirmedDownAt,
        ]);
    }

    /**
     * @return array{site_id: int, user_id: int, last_status: ?string, consecutive_failures: int, confirmed_down_at: ?string, failure_threshold: int}
     */
    public function getConfigurationContext(int $configurationId): array
    {
        $row = SiteCheckConfiguration::join('sites', 'sites.id', '=', 'site_check_configurations.site_id')
            ->where('site_check_configurations.id', $configurationId)
            ->select([
                'site_check_configurations.site_id',
                'site_check_configurations.last_status',
                'site_check_configurations.consecutive_failures',
                'site_check_configurations.confirmed_down_at',
                'site_check_configurations.failure_threshold',
                'sites.user_id',
            ])
            ->lockForUpdate()
            ->firstOrFail();

        return [
            'site_id' => (int)$row->site_id,
            'user_id' => (int)$row->user_id,
            'last_status' => $row->last_status,
            'consecutive_failures' => (int)($row->consecutive_failures ?? 0),
            'confirmed_down_at' => $row->confirmed_down_at,
            'failure_threshold' => (int)($row->failure_threshold ?? 3),
        ];
    }

    public function findPage(int $userId, ?int $projectId, int $perPage, int $page): array
    {
        $sites = EloquentSite::where('user_id', $userId)
            ->with(['latestCheck', 'latestHttpCheck', 'latestSslCheck', 'latestPingCheck'])
            ->when($projectId !== null, fn($q) => $q->where('project_id', $projectId))
            ->latest()
            ->forPage($page, $perPage)
            ->get();

        $siteIntervals = $sites->pluck('update_interval', 'id')->toArray();
        $statsBySiteId = $this->statsRepository->loadForSites($siteIntervals);

        return $sites
            ->map(fn(EloquentSite $site) => $this->mapper->toDomain(
                $site,
                $statsBySiteId[$site->id] ?? null,
                $this->getCachedConfigurations($site->id),
            ))
            ->toArray();
    }

    public function countByFilter(int $userId, ?int $projectId): int
    {
        $key = $this->sitesCacheKey($userId, $projectId, 'count');

        return Cache::remember($key, self::SITES_CACHE_TTL, static function () use ($userId, $projectId) {
            return EloquentSite::where('user_id', $userId)
                ->when($projectId !== null, fn($q) => $q->where('project_id', $projectId))
                ->count();
        });
    }

    public function getStatusCounts(int $userId, ?int $projectId): array
    {
        $key = $this->sitesCacheKey($userId, $projectId, 'status_counts');

        return Cache::remember($key, self::SITES_CACHE_TTL, function () use ($userId, $projectId) {
            $projectClause = '';
            $params = [$userId, $userId];
            if ($projectId !== null) {
                $projectClause = 'AND s.project_id = ?';
                $params[] = $projectId;
            }

            $rows = DB::select(
                "
                WITH latest AS (
                    SELECT DISTINCT ON (r.site_id) r.site_id, r.status
                    FROM check_results r
                    JOIN sites s ON s.id = r.site_id AND s.user_id = ?
                    ORDER BY r.site_id, r.checked_at DESC, r.id DESC
                )
                SELECT
                    COALESCE(l.status, 'pending') AS status,
                    COUNT(*) AS cnt
                FROM sites s
                LEFT JOIN latest l ON l.site_id = s.id
                WHERE s.user_id = ? {$projectClause}
                GROUP BY 1
            ",
                $params
            );

            $counts = ['up' => 0, 'down' => 0, 'slow' => 0, 'pending' => 0];
            foreach ($rows as $row) {
                $counts[$row->status] = (int)$row->cnt;
            }

            return $counts;
        });
    }

    private function sitesCacheKey(int $userId, ?int $projectId, string $suffix): string
    {
        $gen = (int)Cache::get("user_sites_gen:{$userId}", 0);
        $scope = $projectId !== null ? "project:{$projectId}" : 'all';

        return "user_sites_v9:{$userId}:{$scope}:gen{$gen}:{$suffix}";
    }

    /** @return DomainConfiguration[] */
    private function getCachedConfigurations(int $siteId): array
    {
        $version = self::CONFIG_CACHE_VERSION;
        $cached = Cache::remember(
            "site_{$siteId}_configurations_{$version}",
            self::CONFIG_CACHE_TTL,
            function () use ($siteId) {
                return SiteCheckConfiguration::where('site_id', $siteId)
                    ->where('is_active', true)
                    ->with('checkType')
                    ->get()
                    ->map(fn(SiteCheckConfiguration $c) => $this->configurationMapper->toDomain($c)->toArray())
                    ->toArray();
            }
        );

        return array_map(fn(array $data) => $this->configurationMapper->arrayToDomain($data), $cached);
    }

    private function clearConfigurationsCache(int $siteId): void
    {
        $version = self::CONFIG_CACHE_VERSION;
        Cache::forget("site_{$siteId}_configurations_{$version}");
    }
}
