<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PlayerStatus;
use App\Models\Player;
use App\Services\CashFlow\CashFlowService;
use App\Services\Reports\ReportService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashSummary extends StatsOverviewWidget
{
    protected ?string $heading = 'Resumo';

    protected function getStats(): array
    {
        $cashFlow = app(CashFlowService::class);
        $reports = app(ReportService::class);

        $now = CarbonImmutable::now();
        $month = $reports->cashFlowByPeriod($now->startOfMonth(), $now->endOfMonth());

        $balance = $cashFlow->balanceCents();
        $owed = $reports->totalOwedCents();
        $activePlayers = Player::query()->where('status', PlayerStatus::Active)->count();

        return [
            Stat::make('Saldo do caixa', Money::formatBRL($balance))
                ->description($balance >= 0 ? 'Positivo' : 'Negativo')
                ->color($balance >= 0 ? 'success' : 'danger'),

            Stat::make('Receitas x Despesas (mês)', Money::formatBRL($month['balance_cents']))
                ->description(Money::formatBRL($month['income_cents']).' receitas / '.Money::formatBRL($month['expense_cents']).' despesas')
                ->color($month['balance_cents'] >= 0 ? 'success' : 'danger'),

            Stat::make('Inadimplência', Money::formatBRL($owed))
                ->description('Total em aberto (mensalidades + diárias)')
                ->color($owed > 0 ? 'warning' : 'success'),

            Stat::make('Atletas ativos', (string) $activePlayers)
                ->description('Cadastro ativo')
                ->color('info'),
        ];
    }
}
