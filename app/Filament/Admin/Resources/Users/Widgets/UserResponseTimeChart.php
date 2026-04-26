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

        $days = match ($this->filter) {
            'today' => 0,
            'week' => 7,
            'month' => 30,
            default => 7,
        };

        $since = $days === 0 ? now()->startOfDay() : now()->subDays($days);
        $siteIds = $this->record->sites()->pluck('id');

        // 1. Get Trend from raw data (CheckResult)
        $rawTrend = Trend::query(
            CheckResult::query()->whereHas('configuration', function ($query) use ($siteIds) {
                $query->whereIn('site_id', $siteIds);
            })
        )
            ->between(start: $since, end: now());

        $data = $days > 7 ? $rawTrend->perDay()->average('response_time_ms') : $rawTrend->perHour()->average('response_time_ms');

        if ($days === 7) {
            $data = $data->nth(8);
        }

        $chartData = $data->mapWithKeys(fn (TrendValue $value) => [$value->date => $value->aggregate])->toArray();

        // 2. If 'month', add archived data for the gap (days 8-30)
        if ($this->filter === 'month') {
            $archiveAggregated = [];
            $archives = CheckResultArchive::whereIn('site_id', $siteIds)
                ->where('created_at', '>=', $since)
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
            'labels' => array_map(static function ($date) use ($days) {
                return $days <= 1 ? $date : Carbon::parse($date)->format('M d H:i');
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
