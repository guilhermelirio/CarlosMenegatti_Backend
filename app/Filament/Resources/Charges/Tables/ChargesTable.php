<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges\Tables;

use App\Enums\FeeStatus;
use App\Enums\PaymentMethod;
use App\Filament\Support\MoneyField;
use App\Models\Charge;
use App\Models\MonthlyFee;
use App\Services\Billing\MonthlyFeeAdjustmentService;
use App\Services\Billing\PaymentService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
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
                SelectFilter::make('player')
                    ->label('Atleta')
                    ->relationship('player', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('period')
                    ->label('Competência / período')
                    ->schema([
                        DatePicker::make('from')
                            ->label('De')
                            ->beforeOrEqual('to'),
                        DatePicker::make('to')
                            ->label('Até')
                            ->afterOrEqual('from'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('reference_date', '>=', $date))
                        ->when($data['to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('reference_date', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('Desde '.CarbonImmutable::parse($data['from'])->format('d/m/Y'))
                                ->removeField('from');
                        }

                        if ($data['to'] ?? null) {
                            $indicators[] = Indicator::make('Até '.CarbonImmutable::parse($data['to'])->format('d/m/Y'))
                                ->removeField('to');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                self::discountAction(),
                self::exemptAction(),
                self::receivePaymentAction(),
            ])
            ->defaultSort('reference_date', 'desc');
    }

    private static function discountAction(): Action
    {
        return Action::make('discount')
            ->label('Desconto')
            ->icon('heroicon-o-receipt-percent')
            ->visible(fn (Charge $record): bool => $record->charge_type === 'monthly' && ! $record->status->isSettled())
            ->schema([
                MoneyField::make('fixed_cents', 'Valor fixo')->default(0),
                TextInput::make('percent')
                    ->label('Percentual')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(0),
            ])
            ->action(function (Charge $record, array $data): void {
                $fee = $record->underlying();

                if (! $fee instanceof MonthlyFee) {
                    return;
                }

                app(MonthlyFeeAdjustmentService::class)->applyDiscount(
                    $fee,
                    (int) ($data['fixed_cents'] ?? 0),
                    (int) ($data['percent'] ?? 0),
                );

                Notification::make()->title('Desconto aplicado.')->success()->send();
            });
    }

    private static function exemptAction(): Action
    {
        return Action::make('exempt')
            ->label('Isentar')
            ->icon('heroicon-o-gift')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (Charge $record): bool => $record->charge_type === 'monthly' && ! $record->status->isSettled())
            ->action(function (Charge $record): void {
                $fee = $record->underlying();

                if ($fee instanceof MonthlyFee) {
                    app(MonthlyFeeAdjustmentService::class)->exempt($fee);
                    Notification::make()->title('Mensalidade isentada.')->success()->send();
                }
            });
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
