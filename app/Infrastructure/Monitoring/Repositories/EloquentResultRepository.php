<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\ResultRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteStatsRepositoryInterface;
use App\Domain\Monitoring\Data\MonitoringResultData;
use App\Models\CheckResult;
use Illuminate\Support\Facades\DB;

class EloquentResultRepository implements ResultRepositoryInterface
{
    public function __construct(
        private readonly SiteStatsRepositoryInterface $statsRepository,
    ) {}

    public function save(MonitoringResultData $dto): void
    {
        $result = CheckResult::create([
            'site_id' => $dto->siteId,
            'configuration_id' => $dto->configurationId,
            'status' => $dto->status,
            'response_time_ms' => $dto->responseTimeMs,
            'error_message' => $dto->errorMessage,
            'metadata' => $dto->metadata,
            'checked_at' => now(),
        ]);

        $siteId = $result->site_id;

        if ($siteId) {
            DB::afterCommit(fn () => $this->statsRepository->clearCache($siteId));
        }
    }
}
