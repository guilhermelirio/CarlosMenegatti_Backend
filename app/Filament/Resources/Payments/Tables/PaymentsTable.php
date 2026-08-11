<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Billing\PaymentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payable_type')
                    ->label('Referente a')
                    ->formatStateUsing(fn (string $state) => class_basename($state) === 'MonthlyFee' ? 'Mensalidade' : 'Diária')
                    ->badge(),
                TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->money('BRL', divideBy: 100)
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Método')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('pix_txid')
                    ->label('Pix TXID')
                    ->limit(12)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label('Pago em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('receipt_path')
                    ->label('Comprovante')
                    ->boolean(fn (?string $state): bool => filled($state)),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(PaymentStatus::class),
                SelectFilter::make('method')->label('Método')->options(PaymentMethod::class),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $r) => $r->status === PaymentStatus::Pending)
                    ->action(function (Payment $record): void {
                        app(PaymentService::class)->confirm($record);

                        Notification::make()->title('Pagamento confirmado e baixa efetuada.')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
