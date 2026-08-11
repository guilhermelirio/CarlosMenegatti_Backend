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
use App\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvController extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        ReportService $reports,
        CurrentOrganization $currentOrganization,
    ): StreamedResponse {
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
            $cash = $reports->cashFlowByPeriod($from, $to, $filter);
            $incomeBySource = $reports->incomeBySource($from, $to, $filter);
            $delinquency = $reports->delinquencyDetailed($filter);

            return response()->streamDownload(function () use ($organization, $from, $to, $cash, $incomeBySource, $delinquency): void {
                $output = fopen('php://output', 'wb');
                abort_if($output === false, 500);
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, ['Organização', $organization->name], ';', '"', '');
                fputcsv($output, ['Período', $from->format('d/m/Y').' a '.$to->format('d/m/Y')], ';', '"', '');
                fputcsv($output, [], ';', '"', '');
                fputcsv($output, ['Resumo', 'Valor em centavos'], ';', '"', '');
                fputcsv($output, ['Receitas', $cash['income_cents']], ';', '"', '');
                fputcsv($output, ['Despesas', $cash['expense_cents']], ';', '"', '');
                fputcsv($output, ['Saldo', $cash['balance_cents']], ';', '"', '');
                fputcsv($output, [], ';', '"', '');
                fputcsv($output, ['Receita por categoria', 'Valor em centavos'], ';', '"', '');
                foreach ($incomeBySource as $row) {
                    fputcsv($output, [$row['category'], $row['total_cents']], ';', '"', '');
                }
                fputcsv($output, [], ';', '"', '');
                fputcsv($output, ['Inadimplência atual', 'Cobranças vencidas', 'Meses em atraso', 'Total em centavos'], ';', '"', '');
                foreach ($delinquency as $row) {
                    fputcsv($output, [$row['player_name'], $row['open_charges'], $row['months_late'], $row['total_owed_cents']], ';', '"', '');
                }
                fclose($output);
            }, 'relatorio-'.$from->format('Y-m-d').'-'.$to->format('Y-m-d').'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } finally {
            $currentOrganization->clear();
        }
    }
}
