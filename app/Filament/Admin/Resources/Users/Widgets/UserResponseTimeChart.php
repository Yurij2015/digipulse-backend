<?php

namespace App\Filament\Admin\Resources\Users\Widgets;

use App\Models\CheckResult;
use App\Models\CheckResultArchive;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Model;

class UserResponseTimeChart extends ChartWidget
{
    public ?Model $record = null;

    protected ?string $heading = 'Average Response Time (ms)';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last 7 Days',
            'month' => 'Last 30 Days',
        ];
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        [$start, $end, $periodLabel] = match ($this->filter) {
            'today' => [now()->startOfDay(), now(), 'today'],
            'week' => [now()->subDays(6)->startOfDay(), now(), 'week'],
            'month' => [now()->subDays(29)->startOfDay(), now(), 'month'],
            default => [now()->subDays(6)->startOfDay(), now(), 'week'],
        };

        $siteIds = $this->record->sites()->pluck('id');

        // 1. Get Trend from raw data (CheckResult)
        $rawTrend = Trend::query(
            CheckResult::query()->whereHas('configuration', function ($query) use ($siteIds) {
                $query->whereIn('site_id', $siteIds);
            })
        )
            ->dateColumn('checked_at')
            ->between(start: $start, end: $end);

        $data = $periodLabel === 'today'
            ? $rawTrend->perHour()->average('response_time_ms')
            : $rawTrend->perDay()->average('response_time_ms');

        $chartData = $data->mapWithKeys(fn (TrendValue $value) => [$value->date => $value->aggregate])->toArray();

        // 2. If 'month', add archived data for the gap (days 8-30)
        if ($this->filter === 'month') {
            $archiveAggregated = [];
            $archives = CheckResultArchive::whereIn('site_id', $siteIds)
                ->where('created_at', '>=', $start)
                ->get();

            foreach ($archives as $archive) {
                foreach ($archive->data as $result) {
                    $date = \Illuminate\Support\Carbon::parse($result['checked_at'])->format('Y-m-d');
                    if (! isset($archiveAggregated[$date])) {
                        $archiveAggregated[$date] = ['total' => 0, 'count' => 0];
                    }
                    $archiveAggregated[$date]['total'] += $result['response_time_ms'];
                    $archiveAggregated[$date]['count']++;
                }
            }

            if (! empty($archiveAggregated)) {
                foreach ($archiveAggregated as $date => $stats) {
                    // Only use archive if we don't already have raw data for that day (or combine them)
                    // Usually raw data is more recent
                    if (! isset($chartData[$date]) || $chartData[$date] == 0) {
                        $chartData[$date] = round($stats['total'] / $stats['count'], 2);
                    }
                }
                ksort($chartData);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Response Time',
                    'data' => array_values($chartData),
                    'borderColor' => '#8b5cf6',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                ],
            ],
            'labels' => array_map(static function ($date) use ($periodLabel) {
                return $periodLabel === 'today'
                    ? Carbon::parse($date)->format('H:i')
                    : Carbon::parse($date)->format('M d');
            }, array_keys($chartData)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => true,
                        'maxTicksLimit' => 10,
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return value + " ms"; }',
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
