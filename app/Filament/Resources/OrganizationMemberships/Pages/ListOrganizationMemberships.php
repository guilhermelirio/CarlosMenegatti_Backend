<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationMemberships\Pages;

use App\Enums\OrganizationRole;
use App\Filament\Resources\OrganizationMemberships\OrganizationMembershipResource;
use App\Models\Organization;
use App\Services\Organizations\OrganizationMembershipService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use RuntimeException;

class ListOrganizationMemberships extends ListRecords
{
    protected static string $resource = OrganizationMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('attachExisting')
                ->label('Vincular usuário existente')
                ->icon('heroicon-o-link')
                ->schema([
                    TextInput::make('email')->label('E-mail')->email()->required(),
                    Select::make('role')
                        ->label('Papel')
                        ->options(OrganizationRole::class)
                        ->default(OrganizationRole::Member->value)
                        ->required(),
                ])
                ->action(function (array $data, OrganizationMembershipService $service): void {
                    $organization = Filament::getTenant();

                    if (! $organization instanceof Organization) {
                        throw new RuntimeException('Organização atual não encontrada.');
                    }

                    $service->attachExisting(
                        $organization,
                        (string) $data['email'],
                        OrganizationRole::from((string) $data['role']),
                    );

                    Notification::make()->success()->title('Usuário vinculado.')->send();
                }),
            CreateAction::make()->label('Criar novo usuário'),
        ];
    }
}
