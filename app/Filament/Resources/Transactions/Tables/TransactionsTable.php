<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\TransactionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('occurred_on', 'desc');
    }
}
