<?php

declare(strict_types=1);

namespace App\Filament\Resources\DailyFees\Tables;

use App\Enums\FeeStatus;
use App\Enums\PaymentMethod;
use App\Models\DailyFee;
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

class DailyFeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gameSession.scheduled_date')
                    ->label('Sessão')
                    ->date('d/m/Y')
                    ->sortable(),
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
                SelectFilter::make('status')->label('Status')->options(FeeStatus::class),
            ])
            ->recordActions([
                Action::make('receive')
                    ->label('Receber')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (DailyFee $r) => ! $r->status->isSettled())
                    ->schema([
                        Select::make('method')
                            ->label('Método')
                            ->options(PaymentMethod::class)
                            ->default(PaymentMethod::Pix->value)
                            ->required(),
                    ])
                    ->action(function (DailyFee $record, array $data): void {
                        app(PaymentService::class)->registerManualPayment(
                            $record,
                            PaymentMethod::from($data['method']),
                        );

                        Notification::make()->title('Diária recebida e caixa atualizado.')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
