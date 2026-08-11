<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\FeeGenerationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateMonthlyFeesCommand extends Command
{
    protected $signature = 'fees:generate-monthly {--year=} {--month=}';

    protected $description = 'Gera as mensalidades do mês para os atletas mensalistas ativos (idempotente).';

    public function handle(FeeGenerationService $service): int
    {
        $now = CarbonImmutable::now();
        $year = (int) ($this->option('year') ?: $now->year);
        $month = (int) ($this->option('month') ?: $now->month);

        $created = $service->generateForMonth($year, $month);

        $this->info(sprintf('Competência %02d/%04d: %d mensalidade(s) gerada(s).', $month, $year, $created));

        return self::SUCCESS;
    }
}
