<?php

declare(strict_types=1);

namespace App\Filament\Resources\MonthlyClosings\Pages;

use App\Enums\OrganizationRole;
use App\Filament\Resources\MonthlyClosings\MonthlyClosingResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\Reports\MonthlyClosingService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyClosings extends ListRecords
{
    protected static string $resource = MonthlyClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('closeMonth')
                ->label('Fechar mês')
                ->icon('heroicon-o-lock-closed')
                ->visible(fn (): bool => $this->currentRole()?->canManageFinance() ?? false)
                ->schema([
                    Select::make('month')->label('Mês')->options(array_combine(range(1, 12), array_map('strval', range(1, 12))))->required(),
                    Select::make('year')->label('Ano')->options(array_combine(range(2024, 2035), array_map('strval', range(2024, 2035))))->required(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $user = auth()->user();

                    if ($user instanceof User) {
                        app(MonthlyClosingService::class)->close((int) $data['year'], (int) $data['month'], $user);
                        Notification::make()->title('Mês fechado e fotografia financeira salva.')->success()->send();
                    }
                }),
        ];
    }

    private function currentRole(): ?OrganizationRole
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $user instanceof User && $tenant instanceof Organization
            ? $user->roleForOrganization($tenant)
            : null;
    }
}
