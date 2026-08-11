<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PlayerStatus;
use App\Models\Player;
use App\Services\CashFlow\CashFlowService;
use App\Services\Reports\ReportService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $reports = app(ReportService::class);
        $from = $this->filterDate('from', CarbonImmutable::now()->startOfMonth());
        $to = $this->filterDate('to', CarbonImmutable::now()->endOfMonth());

        $period = $reports->cashFlowByPeriod($from, $to);
        $series = $reports->cashFlowSeries(6);
        $incomeSpark = array_map(fn ($r) => round($r['income_cents'] / 100), $series);
        $expenseSpark = array_map(fn ($r) => round($r['expense_cents'] / 100), $series);
        $balanceSpark = array_map(fn ($r) => round($r['balance_cents'] / 100), $series);

        $balance = app(CashFlowService::class)->balanceCents();
        $owed = $reports->totalOwedCents();
        $activePlayers = Player::query()->where('status', PlayerStatus::Active)->count();

        return [
            Stat::make('Saldo do caixa', Money::formatBRL($balance))
                ->description($balance >= 0 ? 'Caixa positivo' : 'Caixa negativo')
                ->descriptionIcon($balance >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($balanceSpark)
                ->color($balance >= 0 ? 'success' : 'danger'),

            Stat::make('Receitas no período', Money::formatBRL($period['income_cents']))
                ->description('Entradas do período selecionado')
                ->descriptionIcon('heroicon-m-arrow-down-left')
                ->chart($incomeSpark)
                ->color('success'),

            Stat::make('Despesas no período', Money::formatBRL($period['expense_cents']))
                ->description('Saídas do período selecionado')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->chart($expenseSpark)
                ->color('danger'),

            Stat::make('Saldo do período', Money::formatBRL($period['balance_cents']))
                ->description('Receitas − Despesas')
                ->chart($balanceSpark)
                ->color($period['balance_cents'] >= 0 ? 'success' : 'danger'),

            Stat::make('Inadimplência', Money::formatBRL($owed))
                ->description('Total em aberto (mensalidades + diárias)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($owed > 0 ? 'warning' : 'success'),

            Stat::make('Atletas ativos', (string) $activePlayers)
                ->description('Cadastros ativos')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }

    private function filterDate(string $key, CarbonImmutable $default): CarbonImmutable
    {
        $value = $this->pageFilters[$key] ?? null;

        return $value ? CarbonImmutable::parse($value) : $default;
    }
}
