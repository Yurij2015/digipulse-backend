<?php

use App\Models\CheckResult;
use App\Models\CheckResultArchive;
use App\Models\SiteCheckConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('archives check results older than 7 days', function () {
    // 1. Setup data
    $config = SiteCheckConfiguration::factory()->create();

    // Recent results (should NOT be archived)
    CheckResult::factory()->count(3)->create([
        'configuration_id' => $config->id,
        'site_id' => $config->site_id,
        'checked_at' => now()->subDays(2),
    ]);

    // Old results (SHOULD be archived)
    // Create results for 8 days ago (Week X)
    $oldDate = now()->subDays(8);
    CheckResult::factory()->count(5)->create([
        'configuration_id' => $config->id,
        'site_id' => $config->site_id,
        'checked_at' => $oldDate,
    ]);

    // 2. Run the command
    Artisan::call('app:archive-check-results');

    // 3. Assertions
    // Main table should only have the 3 recent records
    expect(CheckResult::count())->toBe(3);

    // Archive table should have 1 entry (for the week of 8 days ago)
    expect(CheckResultArchive::count())->toBe(1);

    $archive = CheckResultArchive::first();
    expect($archive->configuration_id)->toBe($config->id);
    expect($archive->year)->toBe((int) $oldDate->format('o'));
    expect($archive->week)->toBe((int) $oldDate->format('W'));
    expect(count($archive->data))->toBe(5);
    expect($archive->size_bytes)->toBeGreaterThan(0);
});

it('purges archives older than 1 year', function () {
    // 1. Setup data
    $config = SiteCheckConfiguration::factory()->create();

    // Valid archive (6 months old)
    CheckResultArchive::factory()->create([
        'configuration_id' => $config->id,
        'site_id' => $config->site_id,
        'created_at' => now()->subMonths(6),
    ]);

    // Expired archive (13 months old)
    CheckResultArchive::factory()->create([
        'configuration_id' => $config->id,
        'site_id' => $config->site_id,
        'created_at' => now()->subMonths(13),
    ]);

    // 2. Run the command
    Artisan::call('app:archive-check-results');

    // 3. Assertions
    expect(CheckResultArchive::count())->toBe(1);
    expect(CheckResultArchive::where('created_at', '<', now()->subYear())->count())->toBe(0);
});
