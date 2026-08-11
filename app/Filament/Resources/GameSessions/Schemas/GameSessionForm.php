<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Schemas;

use App\Filament\Support\MoneyField;
use App\Models\Setting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class GameSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('scheduled_date')
                    ->label('Data')
                    ->default(now())
                    ->required(),
                TimePicker::make('start_time')
                    ->label('Horário')
                    ->seconds(false),
                TextInput::make('location')
                    ->label('Local'),
                MoneyField::make('daily_fee_cents', 'Valor da diária')
                    ->default(fn () => Setting::getInt(Setting::DEFAULT_DAILY_FEE_CENTS, 2000))
                    ->required(),
                Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]);
    }
}
