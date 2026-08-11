<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Filament\Widgets\CashFlowChart;
use App\Filament\Widgets\FinancialStats;
use App\Filament\Widgets\IncomeByCategoryChart;
use App\Filament\Widgets\TopDebtors;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FinancialReports extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/financial-reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?string $title = 'Relatórios';

    protected static ?int $navigationSort = 14;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Período')
                ->schema([
                    DatePicker::make('from')
                        ->label('De')
                        ->default(CarbonImmutable::now()->startOfMonth())
                        ->beforeOrEqual('to'),
                    DatePicker::make('to')
                        ->label('Até')
                        ->default(CarbonImmutable::now()->endOfMonth())
                        ->afterOrEqual('from'),
                ])
                ->columns(2),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            FinancialStats::class,
            CashFlowChart::class,
            IncomeByCategoryChart::class,
            TopDebtors::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('reports.pdf', [
                    'organization' => Filament::getTenant(),
                    'from' => $this->pageFilters['from'] ?? CarbonImmutable::now()->startOfMonth()->toDateString(),
                    'to' => $this->pageFilters['to'] ?? CarbonImmutable::now()->endOfMonth()->toDateString(),
                ]))
                ->openUrlInNewTab(),
            Action::make('csv')
                ->label('Exportar CSV (Excel)')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => route('reports.csv', [
                    'organization' => Filament::getTenant(),
                    'from' => $this->pageFilters['from'] ?? CarbonImmutable::now()->startOfMonth()->toDateString(),
                    'to' => $this->pageFilters['to'] ?? CarbonImmutable::now()->endOfMonth()->toDateString(),
                ])),
        ];
    }
}
