<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Reports\ReportService;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CashFlowChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Fluxo de caixa (período selecionado)';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $from = $this->filterDate('from', CarbonImmutable::now()->startOfMonth());
        $to = $this->filterDate('to', CarbonImmutable::now()->endOfMonth());
        $series = app(ReportService::class)->cashFlowSeriesForPeriod($from, $to);

        return [
            'datasets' => [
                [
                    'label' => 'Receitas',
                    'data' => array_map(fn ($r) => round($r['income_cents'] / 100, 2), $series),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => '#16a34a',
                ],
                [
                    'label' => 'Despesas',
                    'data' => array_map(fn ($r) => round($r['expense_cents'] / 100, 2), $series),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                    'borderColor' => '#dc2626',
                ],
                [
                    'label' => 'Saldo',
                    'data' => array_map(fn ($r) => round($r['balance_cents'] / 100, 2), $series),
                    'type' => 'line',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.2)',
                    'borderColor' => '#ea580c',
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(fn ($r) => $r['label'], $series),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
