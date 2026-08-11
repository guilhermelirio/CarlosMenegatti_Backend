<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\CashFlow\CashFlowService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_on')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40)
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('player.name')
                    ->label('Atleta')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->money('BRL', divideBy: 100)
                    ->color(fn ($record) => $record->type === TransactionType::Income ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(TransactionType::class),
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('player')
                    ->label('Atleta')
                    ->relationship('player', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('period')
                    ->label('Período')
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
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('occurred_on', '>=', $date))
                        ->when($data['to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('occurred_on', '<=', $date)))
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
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('reverse')
                    ->label('Estornar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Transaction $record): bool => $record->reversal_of_id === null && ! $record->trashed())
                    ->schema([
                        Textarea::make('reason')->label('Motivo')->required()->maxLength(500),
                    ])
                    ->action(function (Transaction $record, array $data): void {
                        app(CashFlowService::class)->reverse($record, (string) $data['reason']);
                        Notification::make()->title('Estorno registrado no caixa.')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()->label('Excluir'),
                RestoreAction::make()->label('Restaurar'),
            ])
            ->defaultSort('occurred_on', 'desc');
    }
}
