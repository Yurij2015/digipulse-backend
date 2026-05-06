<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Data\MonitoringResultData;
use App\Domain\Monitoring\UseCases\ProcessMonitoringResult;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Throwable;

#[Signature('app:consume-monitor-results {--once : Process one message and exit}')]
#[Description('Consume monitor results from Redis and persist them.')]
class ConsumeMonitorResults extends Command
{
    public function handle(ProcessMonitoringResult $useCase): int
    {
        if (! config('monitoring.results_consumer.enabled', true)) {
            $this->warn('Monitor results consumer is disabled by configuration.');

            return self::SUCCESS;
        }

        if ($this->option('once')) {
            $this->consumeOnce($useCase);

            return self::SUCCESS;
        }

        $this->info('Starting monitor results consumer...');

        while (true) {
            $this->consumeOnce($useCase);
        }
    }

    private function consumeOnce(ProcessMonitoringResult $useCase): void
    {
        $queue = (string) config('monitoring.results_consumer.queue', 'monitoring:results');
        $failedQueue = (string) config('monitoring.results_consumer.failed_queue', 'monitoring:results:failed');
        $blockSeconds = (int) config('monitoring.results_consumer.block_seconds', 5);
        $maxAttempts = (int) config('monitoring.results_consumer.max_attempts', 5);

        /** @var array{0:string,1:string}|null $result */
        $result = Redis::brpop([$queue], $blockSeconds);
        if (! is_array($result) || count($result) < 2) {
            return;
        }

        $payload = json_decode($result[1], true);
        if (! is_array($payload)) {
            $this->warn('Skipping invalid monitor result payload: invalid JSON.');

            return;
        }

        $attempt = isset($payload['_attempt']) ? (int) $payload['_attempt'] : 1;
        unset($payload['_attempt']);

        $validator = Validator::make($payload, [
            'configuration_id' => ['required', 'integer', 'exists:site_check_configurations,id'],
            'status' => ['required', 'string', 'in:up,down,slow'],
            'response_time_ms' => ['nullable', 'integer'],
            'error_message' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            $this->warn('Skipping invalid monitor result payload: '.$validator->errors()->first());

            return;
        }

        $validated = $validator->validated();

        $dto = new MonitoringResultData(
            configurationId: $validated['configuration_id'],
            status: $validated['status'],
            responseTimeMs: $validated['response_time_ms'] ?? null,
            errorMessage: $validated['error_message'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );

        try {
            DB::transaction(fn () => $useCase->execute($dto));

            $this->info(sprintf(
                '[%s] Successfully processed result for Configuration ID: %d (Status: %s)',
                now()->toDateTimeString(),
                $dto->configurationId,
                $dto->status
            ));
        } catch (Throwable $exception) {
            $retryPayload = $validated;
            $retryPayload['_attempt'] = $attempt + 1;

            if ($attempt >= $maxAttempts) {
                Redis::lpush($failedQueue, (string) json_encode($retryPayload));
                $this->error(sprintf(
                    'Failed to process monitor result for configuration_id=%d after %d attempts. Moved to %s. Last error: %s',
                    $dto->configurationId,
                    $attempt,
                    $failedQueue,
                    $exception->getMessage()
                ));

                return;
            }

            Redis::lpush($queue, (string) json_encode($retryPayload));
            $this->error(sprintf(
                'Failed to process monitor result for configuration_id=%d (attempt %d/%d). Requeued to %s. Error: %s',
                $dto->configurationId,
                $attempt,
                $maxAttempts,
                $queue,
                $exception->getMessage()
            ));
        }
    }
}
