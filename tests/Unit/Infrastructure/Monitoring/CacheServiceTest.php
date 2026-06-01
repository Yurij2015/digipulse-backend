<?php

use App\Infrastructure\Monitoring\Cache\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

test('clearUserSitesCache increments the user generation key', function () {
    Cache::flush();

    Cache::put('user_sites_gen:42', 3, 3600);

    app(CacheService::class)->clearUserSitesCache(42);

    expect(Cache::get('user_sites_gen:42'))->toBe(4);
});

test('clearUserSitesCache starts generation at 1 when missing', function () {
    Cache::flush();

    app(CacheService::class)->clearUserSitesCache(99);

    expect(Cache::get('user_sites_gen:99'))->toBe(1);
});
