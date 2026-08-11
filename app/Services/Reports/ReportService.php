<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\FeeStatus;
use App\Enums\TransactionType;
use App\Models\Player;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class ReportService
{
    /**
     * Inadimplência detalhada: por atleta, nº de cobranças vencidas (mensalidades
     * + diárias), meses em atraso (da mensalidade vencida mais antiga) e total devido.
     *
     * @return list<array<string, int|string>>
     */
    public function delinquencyDetailed(?FinancialReportFilter $filter = null): array
    {
        $open = [FeeStatus::Overdue];
        $now = CarbonImmutable::now();

        $players = Player::query();
        $this->applyPlayerFilters($players, $filter);

        return $players
            ->withCount([
                'monthlyFees as open_monthly' => fn ($q) => $q->whereIn('status', $open),
                'dailyFees as open_daily' => fn ($q) => $q->whereIn('status', $open),
            ])
            ->withSum(['monthlyFees as monthly_owed_cents' => fn ($q) => $q->whereIn('status', $open)], 'amount_cents')
            ->withSum(['dailyFees as daily_owed_cents' => fn ($q) => $q->whereIn('status', $open)], 'amount_cents')
            ->withMin(['monthlyFees as oldest_due' => fn ($q) => $q->whereIn('status', $open)], 'due_date')
            ->get()
            ->map(function (Player $p) use ($now): array {
                $oldest = $p->getAttribute('oldest_due');
                $monthsLate = $oldest !== null
                    ? max(0, (int) CarbonImmutable::parse($oldest)->startOfMonth()->diffInMonths($now->startOfMonth()))
                    : 0;

                return [
                    'player_name' => $p->name,
                    'open_charges' => (int) $p->getAttribute('open_monthly') + (int) $p->getAttribute('open_daily'),
                    'months_late' => $monthsLate,
                    'total_owed_cents' => (int) $p->getAttribute('monthly_owed_cents') + (int) $p->getAttribute('daily_owed_cents'),
                ];
            })
            ->filter(fn (array $row) => $row['total_owed_cents'] > 0)
            ->sortByDesc('total_owed_cents')
            ->values()
            ->all();
    }

    /**
     * Série de fluxo de caixa dos últimos N meses (para gráfico).
     *
     * @return list<array{label: string, income_cents: int, expense_cents: int, balance_cents: int}>
     */
    public function cashFlowSeries(int $months = 12): array
    {
        $to = CarbonImmutable::now()->endOfMonth();
        $from = $to->startOfMonth()->subMonths(max(1, $months) - 1);

        return $this->cashFlowSeriesForPeriod($from, $to);
    }

    /**
     * Série mensal limitada ao período selecionado. O primeiro e o último mês
     * consideram apenas os dias efetivamente incluídos no intervalo.
     *
     * @return list<array{label: string, income_cents: int, expense_cents: int, balance_cents: int}>
     */
    public function cashFlowSeriesForPeriod(
        CarbonInterface $from,
        CarbonInterface $to,
        ?FinancialReportFilter $filter = null,
    ): array {
        $fromDate = CarbonImmutable::parse($from->toDateString())->startOfDay();
        $toDate = CarbonImmutable::parse($to->toDateString())->endOfDay();

        if ($fromDate->isAfter($toDate)) {
            [$fromDate, $toDate] = [$toDate->startOfDay(), $fromDate->endOfDay()];
        }

        $series = [];
        $cursor = $fromDate->startOfMonth();
        $lastMonth = $toDate->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $periodStart = $cursor->isBefore($fromDate) ? $fromDate : $cursor->startOfMonth();
            $periodEnd = $cursor->endOfMonth()->isAfter($toDate) ? $toDate : $cursor->endOfMonth();
            $data = $this->cashFlowByPeriod($periodStart, $periodEnd, $filter);

            $series[] = [
                'label' => $cursor->format('m/Y'),
                'income_cents' => $data['income_cents'],
                'expense_cents' => $data['expense_cents'],
                'balance_cents' => $data['balance_cents'],
            ];

            $cursor = $cursor->addMonth();
        }

        return $series;
    }

    public function totalOwedCents(?FinancialReportFilter $filter = null): int
    {
        return array_sum(array_column($this->delinquencyDetailed($filter), 'total_owed_cents'));
    }

    /**
     * Cash flow for a period: income, expense and balance in cents.
     *
     * @return array{income_cents: int, expense_cents: int, balance_cents: int}
     */
    public function cashFlowByPeriod(
        CarbonInterface $from,
        CarbonInterface $to,
        ?FinancialReportFilter $filter = null,
    ): array {
        $base = Transaction::query()
            ->whereBetween('occurred_on', [$from->toDateString(), $to->toDateString()]);
        $this->applyTransactionFilters($base, $filter);

        $income = (int) (clone $base)->where('type', TransactionType::Income)->sum('amount_cents');
        $expense = (int) (clone $base)->where('type', TransactionType::Expense)->sum('amount_cents');

        return [
            'income_cents' => $income,
            'expense_cents' => $expense,
            'balance_cents' => $income - $expense,
        ];
    }

    /**
     * Income grouped by category (source) for a period.
     *
     * @return list<array<string, int|string>>
     */
    public function incomeBySource(
        CarbonInterface $from,
        CarbonInterface $to,
        ?FinancialReportFilter $filter = null,
    ): array {
        $transactions = Transaction::query()
            ->selectRaw('category_id, SUM(amount_cents) as total_cents')
            ->with('category:id,name')
            ->where('type', TransactionType::Income)
            ->whereBetween('occurred_on', [$from->toDateString(), $to->toDateString()]);
        $this->applyTransactionFilters($transactions, $filter);

        return $transactions
            ->groupBy('category_id')
            ->get()
            ->map(fn (Transaction $t) => [
                'category' => (string) data_get($t, 'category.name', 'Sem categoria'),
                'total_cents' => (int) $t->getAttribute('total_cents'),
            ])
            ->sortByDesc('total_cents')
            ->values()
            ->all();
    }

    /** @param Builder<Transaction> $query */
    private function applyTransactionFilters(Builder $query, ?FinancialReportFilter $filter): void
    {
        if ($filter === null) {
            return;
        }

        $query
            ->when($filter->playerId, fn (Builder $query, string $playerId) => $query->where('player_id', $playerId))
            ->when($filter->categoryId, fn (Builder $query, string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filter->transactionType, fn (Builder $query, TransactionType $type) => $query->where('type', $type))
            ->when($filter->membershipType, fn (Builder $query, $type) => $query->whereHas(
                'player',
                fn (Builder $playerQuery) => $playerQuery->where('membership_type', $type),
            ));
    }

    /** @param Builder<Player> $query */
    private function applyPlayerFilters(Builder $query, ?FinancialReportFilter $filter): void
    {
        if ($filter === null) {
            return;
        }

        $query
            ->when($filter->playerId, fn (Builder $query, string $playerId) => $query->whereKey($playerId))
            ->when($filter->membershipType, fn (Builder $query, $type) => $query->where('membership_type', $type));
    }
}
