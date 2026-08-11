<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Reports\FinancialReportFilter;
use App\Services\Reports\ReportService;
use App\Support\Money;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class TopDebtors extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.top-debtors';

    protected int|string|array $columnSpan = 1;

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $filter = FinancialReportFilter::fromArray($this->pageFilters);
        $rows = app(ReportService::class)->delinquencyDetailed($filter);

        return [
            'rows' => array_slice($rows, 0, 8),
            'count' => count($rows),
            'total' => array_sum(array_column($rows, 'total_owed_cents')),
            'money' => fn (int $cents) => Money::formatBRL($cents),
        ];
    }
}
