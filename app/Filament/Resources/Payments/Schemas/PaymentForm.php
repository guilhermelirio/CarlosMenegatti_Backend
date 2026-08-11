<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
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
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('payable_type')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('payable_id')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('amount_cents')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                Select::make('method')
                    ->options(PaymentMethod::class)
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->options(PaymentStatus::class)
                    ->disabled()
                    ->dehydrated(false),
                DateTimePicker::make('paid_at')->disabled()->dehydrated(false),
                TextInput::make('pix_txid'),
                Textarea::make('pix_qrcode')
                    ->columnSpanFull(),
                Textarea::make('pix_qrcode_image')
                    ->columnSpanFull(),
                TextInput::make('pix_provider'),
                DateTimePicker::make('pix_expires_at'),
                TextInput::make('metadata'),
                FileUpload::make('receipt_path')
                    ->label('Comprovante (opcional)')
                    ->disk('local')
                    ->directory('payment-receipts')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->downloadable()
                    ->columnSpanFull(),
            ]);
    }
}
