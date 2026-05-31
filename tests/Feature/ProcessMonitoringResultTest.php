<?php

use App\Domain\Monitoring\Contracts\AlertServiceInterface;
use App\Domain\Monitoring\Contracts\CachePortInterface;
use App\Domain\Monitoring\Contracts\ResultRepositoryInterface;
use App\Domain\Monitoring\Contracts\SiteRepositoryInterface;
use App\Domain\Monitoring\Data\MonitoringResultData;
use App\Domain\Monitoring\UseCases\ProcessMonitoringResult;
use App\Events\SiteStatusUpdated;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->siteRepository = Mockery::mock(SiteRepositoryInterface::class);
    $this->resultRepository = Mockery::mock(ResultRepositoryInterface::class);
    $this->alertService = Mockery::mock(AlertServiceInterface::class);
    $this->cachePort = Mockery::mock(CachePortInterface::class);

    $this->useCase = new ProcessMonitoringResult(
        siteRepository: $this->siteRepository,
        resultRepository: $this->resultRepository,
        alertService: $this->alertService,
        cachePort: $this->cachePort,
    );

    $this->context = [
        'site_id' => 1,
        'user_id' => 10,
        'last_status' => 'up',
        'consecutive_failures' => 0,
        'confirmed_down_at' => null,
        'failure_threshold' => 3,
    ];
});

it('does not send an alert on the first down result (below threshold)', function () {
    $this->siteRepository->shouldReceive('getConfigurationContext')
        ->once()->with(5)->andReturn($this->context);
    $this->siteRepository->shouldReceive('updateStatus')
        ->once()->with(5, 'down', 1, null);
    $this->resultRepository->shouldReceive('save')->once();
    $this->alertService->shouldNotReceive('sendSiteDownAlert');
    $this->alertService->shouldNotReceive('sendSiteUpAlert');
    $this->cachePort->shouldReceive('clearUserSitesCache')->once()->with(10);

    Event::fake();

    $dto = new MonitoringResultData(configurationId: 5, status: 'down', responseTimeMs: 500);
    $this->useCase->execute($dto);
});

it('sends a down alert when consecutive failures reach the threshold', function () {
    $this->context['consecutive_failures'] = 2;

    $this->siteRepository->shouldReceive('getConfigurationContext')
        ->once()->with(5)->andReturn($this->context);
    $this->siteRepository->shouldReceive('updateStatus')
        ->once()->with(5, 'down', 3, Mockery::type(DateTimeInterface::class));
    $this->resultRepository->shouldReceive('save')->once();
    $this->alertService->shouldReceive('sendSiteDownAlert')->once()->with(5);
    $this->alertService->shouldNotReceive('sendSiteUpAlert');
    $this->cachePort->shouldReceive('clearUserSitesCache')->once()->with(10);

    Event::fake();

    $dto = new MonitoringResultData(configurationId: 5, status: 'down', responseTimeMs: 500);
    $this->useCase->execute($dto);
});

it('does not send another down alert when already confirmed down', function () {
    $this->context['consecutive_failures'] = 2;
    $this->context['confirmed_down_at'] = now()->toISOString();

    $this->siteRepository->shouldReceive('getConfigurationContext')
        ->once()->with(5)->andReturn($this->context);
    $this->siteRepository->shouldReceive('updateStatus')
        ->once()->with(5, 'down', 3, Mockery::not(null));
    $this->resultRepository->shouldReceive('save')->once();
    $this->alertService->shouldNotReceive('sendSiteDownAlert');
    $this->alertService->shouldNotReceive('sendSiteUpAlert');
    $this->cachePort->shouldReceive('clearUserSitesCache')->once()->with(10);

    Event::fake();

    $dto = new MonitoringResultData(configurationId: 5, status: 'down', responseTimeMs: 0, errorMessage: 'timeout');
    $this->useCase->execute($dto);
});

it('sends a recovery alert when site comes up after a confirmed down', function () {
    $this->context['consecutive_failures'] = 2;
    $this->context['confirmed_down_at'] = now()->toISOString();

    $this->siteRepository->shouldReceive('getConfigurationContext')
        ->once()->with(5)->andReturn($this->context);
    $this->siteRepository->shouldReceive('updateStatus')
        ->once()->with(5, 'up', 0, null);
    $this->resultRepository->shouldReceive('save')->once();
    $this->alertService->shouldNotReceive('sendSiteDownAlert');
    $this->alertService->shouldReceive('sendSiteUpAlert')->once()->with(5);
    $this->cachePort->shouldReceive('clearUserSitesCache')->once()->with(10);

    Event::fake();

    $dto = new MonitoringResultData(configurationId: 5, status: 'up', responseTimeMs: 100);
    $this->useCase->execute($dto);
});

it('does not send a recovery alert when site recovers before confirmation', function () {
    $this->context['consecutive_failures'] = 1;
    $this->context['confirmed_down_at'] = null;

    $this->siteRepository->shouldReceive('getConfigurationContext')
        ->once()->with(5)->andReturn($this->context);
    $this->siteRepository->shouldReceive('updateStatus')
        ->once()->with(5, 'up', 0, null);
    $this->resultRepository->shouldReceive('save')->once();
    $this->alertService->shouldNotReceive('sendSiteDownAlert');
    $this->alertService->shouldNotReceive('sendSiteUpAlert');
    $this->cachePort->shouldReceive('clearUserSitesCache')->once()->with(10);

    Event::fake();

    $dto = new MonitoringResultData(configurationId: 5, status: 'up', responseTimeMs: 80);
    $this->useCase->execute($dto);
});

it('does not send any alert when status remains up', function () {
    $this->siteRepository->shouldReceive('getConfigurationContext')
        ->once()->with(5)->andReturn($this->context);
    $this->siteRepository->shouldReceive('updateStatus')
        ->once()->with(5, 'up', 0, null);
    $this->resultRepository->shouldReceive('save')->once();
    $this->alertService->shouldNotReceive('sendSiteDownAlert');
    $this->alertService->shouldNotReceive('sendSiteUpAlert');
    $this->cachePort->shouldReceive('clearUserSitesCache')->once()->with(10);

    Event::fake();

    $dto = new MonitoringResultData(configurationId: 5, status: 'up', responseTimeMs: 80);
    $this->useCase->execute($dto);
});

it('dispatches SiteStatusUpdated event with correct payload', function () {
    $this->siteRepository->shouldReceive('getConfigurationContext')
        ->once()->with(5)->andReturn($this->context);
    $this->siteRepository->shouldReceive('updateStatus')->once();
    $this->resultRepository->shouldReceive('save')->once();
    $this->cachePort->shouldReceive('clearUserSitesCache')->once();

    Event::fake();

    $dto = new MonitoringResultData(configurationId: 5, status: 'up', responseTimeMs: 150);
    $this->useCase->execute($dto);

    Event::assertDispatched(SiteStatusUpdated::class, function ($event) {
        return $event->userId === 10
            && $event->payload['site_id'] === 1
            && $event->payload['configuration_id'] === 5
            && $event->payload['status'] === 'up'
            && $event->payload['response_time_ms'] === 150;
    });
});
