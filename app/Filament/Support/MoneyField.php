<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

final class MoneyField
{
    /** A TextInput that edits an integer "cents" column as BRL reais. */
    public static function make(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->prefix('R$')
            ->inputMode('decimal')
            ->step('0.01')
            ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : number_format($state / 100, 2, '.', ''))
            ->dehydrateStateUsing(fn (?string $state): ?int => $state === null || $state === '' ? null : (int) round(((float) $state) * 100));
    }
}
