<?php

namespace App\Infrastructure\Monitoring\Repositories;

use App\Domain\Monitoring\Contracts\ResultRepositoryInterface;
use App\Domain\Monitoring\DTOs\MonitoringResultDTO;
use App\Models\CheckResult;

class EloquentResultRepository implements ResultRepositoryInterface
{
    public function save(MonitoringResultDTO $result): void
    {
        CheckResult::create([
            'site_id' => $result->siteId,
            'configuration_id' => $result->configurationId,
            'status' => $result->status,
            'response_time_ms' => $result->responseTimeMs,
            'error_message' => $result->errorMessage,
            'metadata' => $result->metadata,
            'checked_at' => now(),
        ]);
    }
}
