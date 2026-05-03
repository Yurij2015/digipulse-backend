<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\ResultRepositoryInterface;
use App\Domain\Monitoring\Data\MonitoringResultData;
use App\Models\CheckResult;

class EloquentResultRepository implements ResultRepositoryInterface
{
    public function save(MonitoringResultData $dto): void
    {
        CheckResult::create([
            'site_id' => $dto->siteId,
            'configuration_id' => $dto->configurationId,
            'status' => $dto->status,
            'response_time_ms' => $dto->responseTimeMs,
            'error_message' => $dto->errorMessage,
            'metadata' => $dto->metadata,
            'checked_at' => now(),
        ]);
    }
}
