<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationMemberships\Pages;

use App\Enums\OrganizationRole;
use App\Filament\Resources\OrganizationMemberships\OrganizationMembershipResource;
use App\Models\OrganizationMembership;
use App\Services\Organizations\OrganizationMembershipService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class EditOrganizationMembership extends EditRecord
{
    protected static string $resource = OrganizationMembershipResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof OrganizationMembership) {
            throw new RuntimeException('Vínculo de organização inválido.');
        }

        return app(OrganizationMembershipService::class)->updateRole(
            $record,
            OrganizationRole::from((string) $data['role']),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Remover acesso')
                ->action(fn (OrganizationMembership $record, OrganizationMembershipService $service) => $service->remove($record)),
        ];
    }
}
