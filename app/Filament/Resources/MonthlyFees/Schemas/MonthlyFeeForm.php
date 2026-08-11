<?php

declare(strict_types=1);

namespace App\Filament\Resources\MonthlyFees\Schemas;

use App\Enums\FeeStatus;
use App\Filament\Support\MoneyField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class MonthlyFeeForm
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
                Select::make('reference_month')
                    ->label('Mês')
                    ->options(array_combine(range(1, 12), array_map('strval', range(1, 12))))
                    ->default((int) date('n'))
                    ->required(),
                Select::make('reference_year')
                    ->label('Ano')
                    ->options(array_combine(range(2024, 2030), array_map('strval', range(2024, 2030))))
                    ->default((int) date('Y'))
                    ->required(),
                MoneyField::make('amount_cents', 'Valor')->required(),
                DatePicker::make('due_date')
                    ->label('Vencimento')
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(FeeStatus::class)
                    ->default(FeeStatus::Pending->value)
                    ->required(),
            ]);
    }
}
