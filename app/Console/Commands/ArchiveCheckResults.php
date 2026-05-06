<?php

namespace App\Console\Commands;

use App\Models\CheckResult;
use App\Models\CheckResultArchive;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('app:archive-check-results')]
#[Description('Archive check results older than 7 days into weekly chunks and purge archives older than 1 year.')]
class ArchiveCheckResults extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting check results archival process...');
        Log::info('Check results archival process started.');

        $this->archiveOldResults();
        $this->purgeExpiredArchives();

        $this->info('Archival process completed successfully.');
        Log::info('Check results archival process completed.');
    }

    /**
     * Move results older than 7 days to archives.
     */
    private function archiveOldResults(): void
    {
        $cutOffDate = now()->subDays(7)->endOfDay();

        // Get unique configuration IDs that have old results
        $configIds = CheckResult::where('checked_at', '<=', $cutOffDate)
            ->distinct()
            ->pluck('configuration_id');

        foreach ($configIds as $configId) {
            $this->comment("Processing Configuration ID: {$configId}");

            // Process in chunks to avoid loading all records into memory at once
            CheckResult::where('configuration_id', $configId)
                ->where('checked_at', '<=', $cutOffDate)
                ->orderBy('checked_at')
                ->chunkById(500, function ($chunk) use ($configId) {
                    $grouped = $chunk->groupBy(fn ($result) => $result->checked_at->format('o-W'));

                    foreach ($grouped as $yearWeek => $results) {
                        [$year, $week] = explode('-', $yearWeek);

                        DB::transaction(static function () use ($configId, $year, $week, $results) {
                            $siteId = $results->first()->site_id;
                            $newData = $results->toArray();

                            $existingArchive = CheckResultArchive::where([
                                'configuration_id' => $configId,
                                'year' => (int) $year,
                                'week' => (int) $week,
                            ])->first();

                            if ($existingArchive) {
                                $combinedData = array_merge($existingArchive->data, $newData);
                                $existingArchive->update([
                                    'data' => $combinedData,
                                    'size_bytes' => strlen(json_encode($combinedData, JSON_THROW_ON_ERROR)),
                                ]);
                            } else {
                                CheckResultArchive::create([
                                    'site_id' => $siteId,
                                    'configuration_id' => $configId,
                                    'year' => (int) $year,
                                    'week' => (int) $week,
                                    'data' => $newData,
                                    'size_bytes' => strlen(json_encode($newData, JSON_THROW_ON_ERROR)),
                                ]);
                            }

                            // Delete the archived records
                            CheckResult::whereIn('id', $results->pluck('id'))->delete();
                        });

                        $logMsg = "Archived Week {$week} of {$year} for Config ID {$configId} ({$results->count()} records)";
                        $this->line("  - {$logMsg}");
                        Log::info($logMsg);
                    }
                });
        }
    }

    /**
     * Delete archives older than 1 year.
     */
    private function purgeExpiredArchives(): void
    {
        $this->info('Purging archives older than 1 year...');

        $deletedCount = CheckResultArchive::where('created_at', '<', now()->subYear())
            ->delete();

        if ($deletedCount > 0) {
            $msg = "Deleted {$deletedCount} expired archive entries older than 1 year.";
            $this->warn($msg);
            Log::info($msg);
        }
    }
}
