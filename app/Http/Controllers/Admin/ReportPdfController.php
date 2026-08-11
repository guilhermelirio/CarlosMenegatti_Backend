<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\Reports\ReportService;
use App\Support\Money;
use App\Tenancy\CurrentOrganization;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
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
            ->wherePivot('role', OrganizationRole::Admin->value)
            ->exists(), 404);

        $currentOrganization->set($organization);

        try {
            $from = CarbonImmutable::parse($request->query('from', CarbonImmutable::now()->startOfMonth()->toDateString()));
            $to = CarbonImmutable::parse($request->query('to', CarbonImmutable::now()->endOfMonth()->toDateString()));

            $delinquency = $reports->delinquencyDetailed();

            $data = [
                'from' => $from,
                'to' => $to,
                'periodLabel' => $from->format('d/m/Y').' – '.$to->format('d/m/Y'),
                'generatedAt' => CarbonImmutable::now()->format('d/m/Y H:i'),
                'cash' => $reports->cashFlowByPeriod($from, $to),
                'series' => $reports->cashFlowSeries(6),
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
