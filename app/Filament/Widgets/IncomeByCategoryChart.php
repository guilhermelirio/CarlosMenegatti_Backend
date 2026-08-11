<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Reports\FinancialReportFilter;
use App\Services\Reports\ReportService;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class IncomeByCategoryChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Receita por categoria (período)';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $from = $this->filterDate('from', CarbonImmutable::now()->startOfMonth());
        $to = $this->filterDate('to', CarbonImmutable::now()->endOfMonth());

        $filter = FinancialReportFilter::fromArray($this->pageFilters);
        $rows = app(ReportService::class)->incomeBySource($from, $to, $filter);

        $palette = ['#16a34a', '#f97316', '#0ea5e9', '#a855f7', '#eab308', '#ef4444', '#14b8a6'];
        $colors = [];
        foreach (array_keys($rows) as $i) {
            $colors[] = $palette[$i % count($palette)];
        }

        return [
            'datasets' => [[
                'label' => 'Receita',
                'data' => array_map(fn ($r) => round($r['total_cents'] / 100, 2), $rows),
                'backgroundColor' => $colors,
            ]],
            'labels' => array_map(fn ($r) => $r['category'], $rows),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return ['animation' => false];
    }

    private function filterDate(string $key, CarbonImmutable $default): CarbonImmutable
    {
        $value = $this->pageFilters[$key] ?? null;

        return $value ? CarbonImmutable::parse($value) : $default;
    }
}
