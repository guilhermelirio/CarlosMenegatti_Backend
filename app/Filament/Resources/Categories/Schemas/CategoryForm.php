<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\TransactionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                Select::make('type')
                    ->label('Tipo')
                    ->options(TransactionType::class)
                    ->required(),
            ]);
    }
}
