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
use Filament\Forms\Components\Toggle;
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
                Toggle::make('is_permanently_exempt')
                    ->label('Gratuidade permanente')
                    ->helperText('Gera o histórico mensal como isento, sem valor a pagar.'),
                MoneyField::make('monthly_discount_cents', 'Desconto fixo mensal')
                    ->helperText('Pode ser combinado com o desconto percentual.'),
                TextInput::make('monthly_discount_percent')
                    ->label('Desconto mensal')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(0),
                Select::make('user_id')
                    ->label('Login do app (opcional)')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Convidados nunca possuem acesso ao aplicativo.'),
                Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]);
    }
}
