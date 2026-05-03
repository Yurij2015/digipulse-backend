<?php

namespace App\Domain\Monitoring\Contracts;

use App\Domain\Monitoring\Data\MonitoringResultData;

interface ResultRepositoryInterface
{
    /**
     * Save a monitoring result.
     */
    public function save(MonitoringResultData $dto): void;
}
