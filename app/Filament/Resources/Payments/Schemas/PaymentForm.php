<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('player_id')
                    ->relationship('player', 'name')
                    ->required(),
                TextInput::make('payable_type')
                    ->required(),
                TextInput::make('payable_id')
                    ->required(),
                TextInput::make('amount_cents')
                    ->required()
                    ->numeric(),
                Select::make('method')
                    ->options(PaymentMethod::class)
                    ->required(),
                Select::make('status')
                    ->options(PaymentStatus::class)
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('paid_at'),
                TextInput::make('pix_txid'),
                Textarea::make('pix_qrcode')
                    ->columnSpanFull(),
                Textarea::make('pix_qrcode_image')
                    ->columnSpanFull(),
                TextInput::make('pix_provider'),
                DateTimePicker::make('pix_expires_at'),
                TextInput::make('metadata'),
            ]);
    }
}
