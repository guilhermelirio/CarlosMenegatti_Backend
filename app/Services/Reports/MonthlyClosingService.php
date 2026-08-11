<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\OrganizationRole;
use App\Models\MonthlyClosing;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MonthlyClosingService
{
    public function __construct(
        private ReportService $reports,
        private AuditService $audit,
    ) {}

    public function close(int $year, int $month, User $user): MonthlyClosing
    {
        $this->ensureCanManageFinance($user);
        $from = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $to = $from->endOfMonth();

        return DB::transaction(function () use ($year, $month, $user, $from, $to): MonthlyClosing {
            $existing = MonthlyClosing::query()
                ->where('reference_year', $year)
                ->where('reference_month', $month)
                ->lockForUpdate()
                ->first();

            if ($existing?->is_closed) {
                throw ValidationException::withMessages(['month' => ['Este mês já está fechado.']]);
            }

            $delinquency = $this->reports->delinquencyDetailed();
            $snapshot = [
                'cash' => $this->reports->cashFlowByPeriod($from, $to),
                'delinquency' => $delinquency,
                'total_owed_cents' => array_sum(array_column($delinquency, 'total_owed_cents')),
                'closed_at' => CarbonImmutable::now()->toIso8601String(),
            ];

            $closing = MonthlyClosing::query()->updateOrCreate(
                ['reference_year' => $year, 'reference_month' => $month],
                [
                    'is_closed' => true,
                    'snapshot' => $snapshot,
                    'closed_at' => CarbonImmutable::now(),
                    'closed_by' => $user->getKey(),
                    'reopened_at' => null,
                    'reopened_by' => null,
                    'reopen_reason' => null,
                ],
            );

            $this->audit->record('month_closed', $closing, after: $closing->attributesToArray());

            return $closing;
        });
    }

    public function reopen(MonthlyClosing $closing, User $user, string $reason): MonthlyClosing
    {
        $this->ensureAdministrator($user);

        if (! $closing->is_closed) {
            throw ValidationException::withMessages(['reason' => ['Este mês já está aberto.']]);
        }

        return DB::transaction(function () use ($closing, $user, $reason): MonthlyClosing {
            $before = $closing->attributesToArray();
            $closing->update([
                'is_closed' => false,
                'reopened_at' => CarbonImmutable::now(),
                'reopened_by' => $user->getKey(),
                'reopen_reason' => $reason,
            ]);
            $this->audit->record('month_reopened', $closing, $before, $closing->fresh()->attributesToArray(), $reason);

            return $closing;
        });
    }

    public function isClosed(CarbonImmutable $date): bool
    {
        return MonthlyClosing::query()
            ->where('reference_year', $date->year)
            ->where('reference_month', $date->month)
            ->where('is_closed', true)
            ->exists();
    }

    private function ensureCanManageFinance(User $user): void
    {
        $role = $user->roleForOrganization((string) app(CurrentOrganization::class)->id());

        if (! $role?->canManageFinance()) {
            throw ValidationException::withMessages(['authorization' => ['Sem permissão para fechar o mês.']]);
        }
    }

    private function ensureAdministrator(User $user): void
    {
        $role = $user->roleForOrganization((string) app(CurrentOrganization::class)->id());

        if ($role !== OrganizationRole::Admin) {
            throw ValidationException::withMessages(['authorization' => ['Somente administrador pode reabrir o mês.']]);
        }
    }
}
