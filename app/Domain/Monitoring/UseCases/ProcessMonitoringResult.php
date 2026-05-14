<?php

namespace App\Domain\Monitoring\UseCases;

use App\Domain\Monitoring\Contracts\AlertServiceInterface;
use App\Domain\Monitoring\Contracts\CachePortInterface;
use App\Domain\Monitoring\Contracts\ResultRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
use App\Domain\Monitoring\Data\MonitoringResultData;
use App\Events\SiteStatusUpdated;

/**
 * Use Case for processing a new monitoring result.
 * This is the central business logic of the monitoring module.
 */
readonly class ProcessMonitoringResult
{
    public function __construct(
        private SiteRepositoryInterface $siteRepository,
        private ResultRepositoryInterface $resultRepository,
        private AlertServiceInterface $alertService,
        private CachePortInterface $cachePort,
    ) {}

    /**
     * Execute the use case.
     */
    public function execute(MonitoringResultData $dto): void
    {
        $context = $this->siteRepository->getConfigurationContext($dto->configurationId);

        $enrichedDto = new MonitoringResultData(
            configurationId: $dto->configurationId,
            status: $dto->status,
            responseTimeMs: $dto->responseTimeMs,
            errorMessage: $dto->errorMessage,
            metadata: $dto->metadata,
            siteId: $context['site_id'],
        );

        $this->siteRepository->updateStatus($dto->configurationId, $dto->status);
        $this->resultRepository->save($enrichedDto);

        event(new SiteStatusUpdated(
            userId: $context['user_id'],
            payload: [
                'site_id' => $context['site_id'],
                'configuration_id' => $dto->configurationId,
                'status' => $dto->status,
                'response_time_ms' => $dto->responseTimeMs,
                'checked_at' => now()->toISOString(),
            ],
        ));

        if (($context['last_status'] ?? 'up') !== 'down' && $dto->status === 'down') {
            $this->alertService->sendSiteDownAlert($dto->configurationId);
        }

        if (($context['last_status'] ?? 'up') === 'down' && $dto->status === 'up') {
            $this->alertService->sendSiteUpAlert($dto->configurationId);
        }

        $this->cachePort->clearUserSitesCache($context['user_id']);
    }
}
