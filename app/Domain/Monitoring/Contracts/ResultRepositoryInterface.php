<?php

namespace App\Domain\Monitoring\Contracts;

use App\Domain\Monitoring\DTOs\MonitoringResultDTO;

interface ResultRepositoryInterface
{
    /**
     * Save a monitoring check result to history.
     */
    public function save(MonitoringResultDTO $result): void;
}
