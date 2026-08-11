<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GameSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scheduled_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Horário'),
                TextColumn::make('location')
                    ->label('Local')
                    ->searchable(),
                TextColumn::make('daily_fee_cents')
                    ->label('Diária')
                    ->money('BRL', divideBy: 100)
                    ->sortable(),
                TextColumn::make('attendances_count')
                    ->label('Presenças')
                    ->counts(['attendances' => fn ($q) => $q->where('attended', true)])
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make()->label('Presenças / Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_date', 'desc');
    }
}
