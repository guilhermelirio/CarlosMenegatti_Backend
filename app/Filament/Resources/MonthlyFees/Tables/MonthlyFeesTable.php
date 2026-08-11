<?php

declare(strict_types=1);

namespace App\Filament\Resources\MonthlyFees\Tables;

use App\Enums\FeeStatus;
use App\Enums\PaymentMethod;
use App\Models\MonthlyFee;
use App\Services\Billing\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MonthlyFeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Competência')
                    ->state(fn (MonthlyFee $r) => $r->referenceLabel()),
                TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->money('BRL', divideBy: 100)
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('paid_at')
                    ->label('Pago em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(FeeStatus::class),
                SelectFilter::make('player')
                    ->label('Atleta')
                    ->relationship('player', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('reference_month')
                    ->label('Mês')
                    ->options(array_combine(range(1, 12), array_map('strval', range(1, 12)))),
                SelectFilter::make('reference_year')
                    ->label('Ano')
                    ->options(array_combine(range(2024, 2035), array_map('strval', range(2024, 2035)))),
            ])
            ->recordActions([
                self::receivePaymentAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'desc');
    }

    /** Row action: "Receber pagamento" (dar baixa) for a pending/overdue fee. */
    public static function receivePaymentAction(): Action
    {
        return Action::make('receive')
            ->label('Receber')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (MonthlyFee $r) => ! $r->status->isSettled())
            ->schema([
                Select::make('method')
                    ->label('Método')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::Pix->value)
                    ->required(),
            ])
            ->action(function (MonthlyFee $record, array $data): void {
                app(PaymentService::class)->registerManualPayment(
                    $record,
                    PaymentMethod::from($data['method']),
                );

                Notification::make()
                    ->title('Pagamento recebido e caixa atualizado.')
                    ->success()
                    ->send();
            });
    }
}
