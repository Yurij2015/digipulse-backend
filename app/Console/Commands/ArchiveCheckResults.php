<?php

namespace App\Console\Commands;

use App\Models\CheckResult;
use App\Models\CheckResultArchive;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $this->archiveOldResults();
        $this->purgeExpiredArchives();

        $this->info('Archival process completed successfully.');
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

            // Group results by ISO year and week for this configuration
            $oldResults = CheckResult::where('configuration_id', $configId)
                ->where('checked_at', '<=', $cutOffDate)
                ->orderBy('checked_at')
                ->get()
                ->groupBy(fn ($result) => $result->checked_at->format('o-W'));

            foreach ($oldResults as $yearWeek => $results) {
                [$year, $week] = explode('-', $yearWeek);

                DB::transaction(function () use ($configId, $year, $week, $results) {
                    $siteId = $results->first()->site_id;
                    $newData = $results->map(fn ($r) => $r->getAttributes())->toArray();

                    $existingArchive = CheckResultArchive::where([
                        'configuration_id' => $configId,
                        'year' => (int) $year,
                        'week' => (int) $week,
                    ])->first();

                    if ($existingArchive) {
                        $combinedData = array_merge($existingArchive->data, $newData);
                        $existingArchive->update([
                            'data' => $combinedData,
                            'size_bytes' => $existingArchive->size_bytes + strlen(json_encode($newData)),
                        ]);
                    } else {
                        CheckResultArchive::create([
                            'site_id' => $siteId,
                            'configuration_id' => $configId,
                            'year' => (int) $year,
                            'week' => (int) $week,
                            'data' => $newData,
                            'size_bytes' => strlen(json_encode($newData)),
                        ]);
                    }

                    // Delete the archived records
                    CheckResult::whereIn('id', $results->pluck('id'))->delete();
                });

                $this->line("  - Archived Week {$week} of {$year} ({$results->count()} records)");
            }
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
            $this->warn("Deleted {$deletedCount} expired archive entries.");
        }
    }
}
