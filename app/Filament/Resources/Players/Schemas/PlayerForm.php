<?php

declare(strict_types=1);

namespace App\Filament\Resources\Players\Schemas;

use App\Enums\MembershipType;
use App\Enums\PlayerPosition;
use App\Enums\PlayerStatus;
use App\Filament\Support\MoneyField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('nickname')
                    ->label('Apelido'),
                TextInput::make('phone')
                    ->label('Telefone / WhatsApp')
                    ->tel(),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email(),
                Select::make('position')
                    ->label('Posição')
                    ->options(PlayerPosition::class),
                Select::make('status')
                    ->label('Status')
                    ->options(PlayerStatus::class)
                    ->default(PlayerStatus::Active->value)
                    ->required(),
                Select::make('membership_type')
                    ->label('Tipo de vínculo')
                    ->options(MembershipType::class)
                    ->default(MembershipType::Monthly->value)
                    ->required(),
                DatePicker::make('joined_at')
                    ->label('Data de entrada'),
                MoneyField::make('monthly_fee_cents', 'Mensalidade individual (opcional)')
                    ->helperText('Deixe em branco para usar o valor padrão da configuração.'),
                MoneyField::make('daily_fee_cents', 'Diária individual (opcional)')
                    ->helperText('Deixe em branco para usar o valor padrão da configuração.'),
                Select::make('user_id')
                    ->label('Login do app (opcional)')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]);
    }
}
