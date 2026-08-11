<?php

declare(strict_types=1);

namespace App\Filament\Resources\DailyFees\Schemas;

use App\Enums\FeeStatus;
use App\Filament\Support\MoneyField;
use App\Models\GameSession;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DailyFeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('player_id')
                    ->label('Atleta')
                    ->relationship('player', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('game_session_id')
                    ->label('Sessão de jogo')
                    ->options(fn () => GameSession::query()
                        ->orderByDesc('scheduled_date')
                        ->get()
                        ->mapWithKeys(fn (GameSession $s) => [
                            $s->id => $s->scheduled_date->format('d/m/Y').' - '.($s->location ?? 'Sessão'),
                        ]))
                    ->searchable()
                    ->required(),
                MoneyField::make('amount_cents', 'Valor')->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(FeeStatus::class)
                    ->default(FeeStatus::Pending->value)
                    ->required(),
            ]);
    }
}
