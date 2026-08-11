<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationMemberships\Pages;

use App\Enums\OrganizationRole;
use App\Filament\Resources\OrganizationMemberships\OrganizationMembershipResource;
use App\Models\Organization;
use App\Services\Organizations\OrganizationMembershipService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateOrganizationMembership extends CreateRecord
{
    protected static string $resource = OrganizationMembershipResource::class;

    protected static bool $canCreateAnother = false;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $organization = Filament::getTenant();

        if (! $organization instanceof Organization) {
            throw new RuntimeException('Organização atual não encontrada.');
        }

        return app(OrganizationMembershipService::class)->createUser(
            organization: $organization,
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: (string) $data['password'],
            role: OrganizationRole::from((string) $data['role']),
        );
    }
}
