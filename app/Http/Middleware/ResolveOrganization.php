<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Tenancy\CurrentOrganization;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveOrganization
{
    public function __construct(private readonly CurrentOrganization $currentOrganization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organizations = $request->user()?->organizations()->get() ?? collect();
        $requestedId = $request->header('X-Organization-Id');

        if ($requestedId === null && $organizations->count() > 1) {
            return new JsonResponse([
                'message' => 'Informe a organização no cabeçalho X-Organization-Id.',
                'error_code' => 'ORGANIZATION_REQUIRED',
            ], 422);
        }

        /** @var Organization|null $organization */
        $organization = $requestedId === null
            ? $organizations->first()
            : $organizations->firstWhere('id', $requestedId);

        if ($organization === null) {
            return new JsonResponse([
                'message' => 'Organização não encontrada.',
                'error_code' => 'ORGANIZATION_NOT_FOUND',
            ], 404);
        }

        $this->currentOrganization->set($organization);

        try {
            return $next($request);
        } finally {
            $this->currentOrganization->clear();
        }
    }
}
