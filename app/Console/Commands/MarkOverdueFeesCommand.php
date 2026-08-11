<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Billing\FeeGenerationService;
use App\Tenancy\CurrentOrganization;
use Illuminate\Console\Command;

class MarkOverdueFeesCommand extends Command
{
    protected $signature = 'fees:mark-overdue';

    protected $description = 'Marca como vencidas as mensalidades pendentes cujo vencimento já passou.';

    public function handle(FeeGenerationService $service, CurrentOrganization $currentOrganization): int
    {
        $updated = 0;

        Organization::query()->each(function (Organization $organization) use ($service, $currentOrganization, &$updated): void {
            $currentOrganization->set($organization);
            $updated += $service->markOverdue();
        });

        $currentOrganization->clear();

        $this->info("{$updated} mensalidade(s) marcada(s) como vencida(s).");

        return self::SUCCESS;
    }
}
