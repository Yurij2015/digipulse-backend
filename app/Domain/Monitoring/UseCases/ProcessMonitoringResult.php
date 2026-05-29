<?php

namespace App\Domain\Monitoring\UseCases;

use App\Domain\Monitoring\Contracts\AlertServiceInterface;
use App\Domain\Monitoring\Contracts\CachePortInterface;
use App\Domain\Monitoring\Contracts\ResultRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
use App\Domain\Monitoring\Data\MonitoringResultData;
use App\Events\SiteStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Use Case for processing a new monitoring result.
 * This is the central business logic of the monitoring module.
 */
readonly class ProcessMonitoringResult
{
    // Number of consecutive failures required before sending a down alert.
    private const int FAILURE_THRESHOLD = 3;

    public function __construct(
        private SiteRepositoryInterface $siteRepository,
        private ResultRepositoryInterface $resultRepository,
        private AlertServiceInterface $alertService,
        private CachePortInterface $cachePort,
    ) {}

    /**
     * @throws \Throwable
     */
    public function execute(MonitoringResultData $dto): void
    {
        [$context, $consecutiveFailures, $confirmedDownAt] = DB::transaction(
            function () use ($dto): array {
                $context = $this->siteRepository->getConfigurationContext($dto->configurationId);
                [$consecutiveFailures, $confirmedDownAt] = $this->computeFailureState($dto->status, $context);
                $this->siteRepository->updateStatus($dto->configurationId, $dto->status, $consecutiveFailures, $confirmedDownAt);

                return [$context, $consecutiveFailures, $confirmedDownAt];
            }
        );

        $enrichedDto = new MonitoringResultData(
            configurationId: $dto->configurationId,
            status: $dto->status,
            responseTimeMs: $dto->responseTimeMs,
            errorMessage: $dto->errorMessage,
            metadata: $dto->metadata,
            siteId: $context['site_id'],
        );

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

        $this->dispatchAlerts($dto->status, $dto->configurationId, $context, $consecutiveFailures, $confirmedDownAt);

        $this->cachePort->clearUserSitesCache($context['user_id']);
    }

    /**
     * @return array{0: int, 1: ?\DateTimeInterface}
     */
    private function computeFailureState(string $status, array $context): array
    {
        if ($status === 'up') {
            return [0, null];
        }

        if ($status === 'slow') {
            $existingConfirmedAt = $context['confirmed_down_at'] !== null
                ? now()->parse($context['confirmed_down_at'])
                : null;

            return [(int) $context['consecutive_failures'], $existingConfirmedAt];
        }

        $consecutiveFailures = $context['consecutive_failures'] + 1;

        // Preserve existing confirmed_down_at (raw DB string → Carbon); stamp when threshold is first reached.
        $existingConfirmedAt = $context['confirmed_down_at'] !== null
            ? now()->parse($context['confirmed_down_at'])
            : null;

        $confirmedDownAt = $existingConfirmedAt
            ?? ($consecutiveFailures >= self::FAILURE_THRESHOLD ? now() : null);

        return [$consecutiveFailures, $confirmedDownAt];
    }

    private function dispatchAlerts(
        string $status,
        int $configurationId,
        array $context,
        int $consecutiveFailures,
        mixed $confirmedDownAt,
    ): void {
        if ($status === 'down') {
            Log::info('ProcessMonitoringResult: down result recorded', [
                'configuration_id' => $configurationId,
                'site_id' => $context['site_id'],
                'consecutive_failures' => $consecutiveFailures,
                'threshold' => self::FAILURE_THRESHOLD,
                'confirmed' => $confirmedDownAt !== null,
            ]);

            if ($consecutiveFailures >= self::FAILURE_THRESHOLD && $context['confirmed_down_at'] === null) {
                Log::warning('ProcessMonitoringResult: site confirmed down, sending alert', [
                    'configuration_id' => $configurationId,
                    'site_id' => $context['site_id'],
                    'consecutive_failures' => $consecutiveFailures,
                ]);
                $this->alertService->sendSiteDownAlert($configurationId);
            }

            return;
        }

        if ($status === 'up' && $context['confirmed_down_at'] !== null) {
            Log::info('ProcessMonitoringResult: site recovered from confirmed down, sending recovery alert', [
                'configuration_id' => $configurationId,
                'site_id' => $context['site_id'],
            ]);
            $this->alertService->sendSiteUpAlert($configurationId);
        } elseif ($status === 'up' && $context['consecutive_failures'] > 0) {
            Log::info('ProcessMonitoringResult: site recovered before confirmation, no alert sent', [
                'configuration_id' => $configurationId,
                'site_id' => $context['site_id'],
                'consecutive_failures_reset_from' => $context['consecutive_failures'],
            ]);
        }
    }
}
