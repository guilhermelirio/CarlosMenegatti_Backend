<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\MembershipType;
use App\Enums\TransactionType;
use App\Filament\Widgets\CashFlowChart;
use App\Filament\Widgets\FinancialStats;
use App\Filament\Widgets\IncomeByCategoryChart;
use App\Filament\Widgets\TopDebtors;
use App\Models\Category;
use App\Models\Player;
use App\Services\Reports\FinancialReportFilter;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
            Section::make('Filtros do relatório')
                ->schema([
                    DatePicker::make('from')
                        ->label('De')
                        ->default(CarbonImmutable::now()->startOfMonth())
                        ->beforeOrEqual('to'),
                    DatePicker::make('to')
                        ->label('Até')
                        ->default(CarbonImmutable::now()->endOfMonth())
                        ->afterOrEqual('from'),
                    Select::make('player_id')
                        ->label('Atleta')
                        ->options(fn (): array => Player::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->placeholder('Todos os atletas'),
                    Select::make('membership_type')
                        ->label('Vínculo')
                        ->options(MembershipType::class)
                        ->placeholder('Todos os vínculos'),
                    Select::make('category_id')
                        ->label('Categoria')
                        ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->placeholder('Todas as categorias'),
                    Select::make('transaction_type')
                        ->label('Tipo de lançamento')
                        ->options(TransactionType::class)
                        ->placeholder('Receitas e despesas'),
                ])
                ->columns(3),
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
                ->url(fn (): string => route('reports.pdf', array_merge([
                    'organization' => Filament::getTenant(),
                    'from' => $this->filters['from'] ?? CarbonImmutable::now()->startOfMonth()->toDateString(),
                    'to' => $this->filters['to'] ?? CarbonImmutable::now()->endOfMonth()->toDateString(),
                ], $this->reportFilter()->toQuery())))
                ->openUrlInNewTab(),
            Action::make('csv')
                ->label('Exportar CSV (Excel)')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => route('reports.csv', array_merge([
                    'organization' => Filament::getTenant(),
                    'from' => $this->filters['from'] ?? CarbonImmutable::now()->startOfMonth()->toDateString(),
                    'to' => $this->filters['to'] ?? CarbonImmutable::now()->endOfMonth()->toDateString(),
                ], $this->reportFilter()->toQuery()))),
        ];
    }

    private function reportFilter(): FinancialReportFilter
    {
        return FinancialReportFilter::fromArray($this->filters ?? []);
    }
}
