<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\TransactionType;
use App\Filament\Support\MoneyField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo')
                    ->options(TransactionType::class)
                    ->live()
                    ->required(),
                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship(
                        'category',
                        'name',
                        fn ($query, $get) => $get('type')
                            ? $query->where('type', $get('type'))
                            : $query,
                    )
                    ->searchable()
                    ->preload(),
                MoneyField::make('amount_cents', 'Valor')->required(),
                DatePicker::make('occurred_on')
                    ->label('Data')
                    ->default(now())
                    ->required(),
                Select::make('player_id')
                    ->label('Atleta (opcional)')
                    ->relationship('player', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('description')
                    ->label('Descrição')
                    ->columnSpanFull(),
            ]);
    }
}
