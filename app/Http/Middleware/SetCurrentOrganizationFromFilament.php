<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Tenancy\CurrentOrganization;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetCurrentOrganizationFromFilament
{
    public function __construct(private readonly CurrentOrganization $currentOrganization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Organization) {
            $this->currentOrganization->set($tenant);
        }

        try {
            return $next($request);
        } finally {
            $this->currentOrganization->clear();
        }
    }
}
