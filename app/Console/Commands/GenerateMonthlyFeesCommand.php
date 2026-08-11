<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Billing\FeeGenerationService;
use App\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateMonthlyFeesCommand extends Command
{
    protected $signature = 'fees:generate-monthly {--year=} {--month=}';

    protected $description = 'Gera as mensalidades do mês para os atletas mensalistas ativos (idempotente).';

    public function handle(FeeGenerationService $service, CurrentOrganization $currentOrganization): int
    {
        $now = CarbonImmutable::now();
        $year = (int) ($this->option('year') ?: $now->year);
        $month = (int) ($this->option('month') ?: $now->month);

        $created = 0;

        Organization::query()->each(function (Organization $organization) use ($service, $currentOrganization, $year, $month, &$created): void {
            $currentOrganization->set($organization);
            $created += $service->generateForMonth($year, $month);
        });

        $currentOrganization->clear();

        $this->info(sprintf('Competência %02d/%04d: %d mensalidade(s) gerada(s).', $month, $year, $created));

        return self::SUCCESS;
    }
}
