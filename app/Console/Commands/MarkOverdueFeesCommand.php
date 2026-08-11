<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\FeeGenerationService;
use Illuminate\Console\Command;

class MarkOverdueFeesCommand extends Command
{
    protected $signature = 'fees:mark-overdue';

    protected $description = 'Marca como vencidas as mensalidades pendentes cujo vencimento já passou.';

    public function handle(FeeGenerationService $service): int
    {
        $updated = $service->markOverdue();

        $this->info("{$updated} mensalidade(s) marcada(s) como vencida(s).");

        return self::SUCCESS;
    }
}
