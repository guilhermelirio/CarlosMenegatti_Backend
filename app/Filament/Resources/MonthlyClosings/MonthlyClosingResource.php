<?php

declare(strict_types=1);

namespace App\Filament\Resources\MonthlyClosings;

use App\Enums\OrganizationRole;
use App\Filament\Concerns\AuthorizesOrganizationOperations;
use App\Filament\Resources\MonthlyClosings\Pages\ListMonthlyClosings;
use App\Models\MonthlyClosing;
use App\Models\Organization;
use App\Models\User;
use App\Services\Reports\MonthlyClosingService;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MonthlyClosingResource extends Resource
{
    use AuthorizesOrganizationOperations;

    protected static ?string $model = MonthlyClosing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Fechamentos';

    protected static ?string $modelLabel = 'Fechamento mensal';

    protected static ?string $pluralModelLabel = 'Fechamentos mensais';

    protected static ?int $navigationSort = 15;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Competência')
                    ->state(fn (MonthlyClosing $record): string => $record->referenceLabel())
                    ->sortable(['reference_year', 'reference_month']),
                TextColumn::make('is_closed')
                    ->label('Situação')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Fechado' : 'Reaberto')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('cash_balance')
                    ->label('Saldo do mês')
                    ->state(fn (MonthlyClosing $record): string => Money::formatBRL((int) data_get($record->snapshot, 'cash.balance_cents', 0))),
                TextColumn::make('owed')
                    ->label('Inadimplência fotografada')
                    ->state(fn (MonthlyClosing $record): string => Money::formatBRL((int) data_get($record->snapshot, 'total_owed_cents', 0))),
                TextColumn::make('closedBy.name')->label('Fechado por'),
                TextColumn::make('closed_at')->label('Fechado em')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('reopen')
                    ->label('Reabrir')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->visible(fn (MonthlyClosing $record): bool => $record->is_closed && self::activeRole() === OrganizationRole::Admin)
                    ->schema([
                        Textarea::make('reason')->label('Motivo da reabertura')->required()->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->action(function (MonthlyClosing $record, array $data): void {
                        $user = auth()->user();

                        if ($user instanceof User) {
                            app(MonthlyClosingService::class)->reopen($record, $user, (string) $data['reason']);
                            Notification::make()->title('Mês reaberto com registro no histórico.')->success()->send();
                        }
                    }),
            ])
            ->defaultSort('reference_year', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListMonthlyClosings::route('/')];
    }

    private static function activeRole(): ?OrganizationRole
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $user instanceof User && $tenant instanceof Organization
            ? $user->roleForOrganization($tenant)
            : null;
    }
}
