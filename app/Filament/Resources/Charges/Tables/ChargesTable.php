<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges\Tables;

use App\Enums\FeeStatus;
use App\Enums\PaymentMethod;
use App\Models\Charge;
use App\Services\Billing\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChargesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('player.membership_type')
                    ->label('Tipo de atleta')
                    ->badge(),
                TextColumn::make('charge_type')
                    ->label('Cobrança')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'monthly' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => $state === 'monthly' ? 'Mensalidade' : 'Diária'),
                TextColumn::make('reference_label')
                    ->label('Referência'),
                TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->money('BRL', divideBy: 100)
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
                SelectFilter::make('membership_type')
                    ->label('Tipo de atleta')
                    ->options(['monthly' => 'Mensalista', 'daily' => 'Diarista'])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $q, string $value) => $q->whereHas('player', fn (Builder $p) => $p->where('membership_type', $value)),
                    )),
                SelectFilter::make('charge_type')
                    ->label('Cobrança')
                    ->options(['monthly' => 'Mensalidade', 'daily' => 'Diária']),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(FeeStatus::class),
            ])
            ->recordActions([
                self::receivePaymentAction(),
            ])
            ->defaultSort('reference_date', 'desc');
    }

    /** Dar baixa manual numa cobrança pendente/vencida (resolve o modelo real). */
    public static function receivePaymentAction(): Action
    {
        return Action::make('receive')
            ->label('Receber')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (Charge $record): bool => ! $record->status->isSettled())
            ->schema([
                Select::make('method')
                    ->label('Método')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::Pix->value)
                    ->required(),
            ])
            ->action(function (Charge $record, array $data): void {
                $payable = $record->underlying();

                if ($payable === null) {
                    Notification::make()->title('Cobrança não encontrada.')->danger()->send();

                    return;
                }

                app(PaymentService::class)->registerManualPayment(
                    $payable,
                    PaymentMethod::from($data['method']),
                );

                Notification::make()
                    ->title('Pagamento recebido e caixa atualizado.')
                    ->success()
                    ->send();
            });
    }
}
