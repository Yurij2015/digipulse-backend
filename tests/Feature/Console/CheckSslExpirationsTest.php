<?php

use App\Models\CheckResult;
use App\Models\CheckType;
use App\Models\Site;
use App\Models\SiteCheckConfiguration;
use App\Notifications\SSLExpiringNotification;
use Database\Seeders\CheckTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CheckTypeSeeder::class);
});

function makeSiteWithSslResult(int $daysRemaining): SiteCheckConfiguration
{
    $config = SiteCheckConfiguration::factory()->create([
        'check_type_id' => CheckType::where('slug', 'ssl')->first()->id,
    ]);

    CheckResult::factory()->create([
        'site_id' => $config->site_id,
        'configuration_id' => $config->id,
        'status' => 'up',
        'metadata' => ['days_remaining' => $daysRemaining],
        'checked_at' => now(),
    ]);

    return $config;
}

it('notifies owner when SSL expires within 7 days', function () {
    Notification::fake();

    $config = makeSiteWithSslResult(5);

    Artisan::call('app:check-ssl-expirations');

    Notification::assertSentTo($config->site->user, SSLExpiringNotification::class);
});

it('notifies when exactly 7 days remain', function () {
    Notification::fake();

    $config = makeSiteWithSslResult(7);

    Artisan::call('app:check-ssl-expirations');

    Notification::assertSentTo($config->site->user, SSLExpiringNotification::class);
});

it('does not notify when more than 7 days remain', function () {
    Notification::fake();

    makeSiteWithSslResult(30);

    Artisan::call('app:check-ssl-expirations');

    Notification::assertNothingSent();
});

it('does not send duplicate notifications on the same day', function () {
    Notification::fake();

    $config = makeSiteWithSslResult(3);

    Artisan::call('app:check-ssl-expirations');
    Artisan::call('app:check-ssl-expirations');

    Notification::assertSentToTimes($config->site->user, SSLExpiringNotification::class, 1);
});

it('skips inactive sites', function () {
    Notification::fake();

    $config = makeSiteWithSslResult(2);
    Site::where('id', $config->site_id)->update(['is_active' => false]);

    Artisan::call('app:check-ssl-expirations');

    Notification::assertNothingSent();
});
