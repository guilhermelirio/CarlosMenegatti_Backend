<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MembershipType;
use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\Reports\FinancialReportFilter;
use App\Services\Reports\ReportService;
use App\Support\Money;
use App\Tenancy\CurrentOrganization;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfController extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        ReportService $reports,
        CurrentOrganization $currentOrganization,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User && $user->organizations()
            ->whereKey($organization->getKey())
            ->wherePivotIn('role', array_map(
                fn (OrganizationRole $role): string => $role->value,
                array_filter(OrganizationRole::cases(), fn (OrganizationRole $role): bool => $role->canAccessPanel()),
            ))
            ->exists(), 404);

        $currentOrganization->set($organization);

        try {
            $validated = $request->validate([
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date', 'after_or_equal:from'],
                'player_id' => ['nullable', 'ulid'],
                'membership_type' => ['nullable', Rule::enum(MembershipType::class)],
                'category_id' => ['nullable', 'ulid'],
                'transaction_type' => ['nullable', Rule::enum(TransactionType::class)],
            ]);
            $from = CarbonImmutable::parse($validated['from'] ?? CarbonImmutable::now()->startOfMonth());
            $to = CarbonImmutable::parse($validated['to'] ?? CarbonImmutable::now()->endOfMonth());
            $filter = FinancialReportFilter::fromArray($validated);

            $delinquency = $reports->delinquencyDetailed($filter);

            $data = [
                'from' => $from,
                'to' => $to,
                'periodLabel' => $from->format('d/m/Y').' – '.$to->format('d/m/Y'),
                'generatedAt' => CarbonImmutable::now()->format('d/m/Y H:i'),
                'cash' => $reports->cashFlowByPeriod($from, $to, $filter),
                'series' => $reports->cashFlowSeriesForPeriod($from, $to, $filter),
                'delinquency' => $delinquency,
                'totalOwed' => array_sum(array_column($delinquency, 'total_owed_cents')),
                'money' => fn (int $cents) => Money::formatBRL($cents),
                'organizationName' => $organization->name,
            ];

            return Pdf::loadView('reports.pdf', $data)
                ->setPaper('a4')
                ->stream('relatorio-'.$from->format('Y-m-d').'.pdf');
        } finally {
            $currentOrganization->clear();
        }
    }
}
