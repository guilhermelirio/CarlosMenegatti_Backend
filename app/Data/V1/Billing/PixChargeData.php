<?php

declare(strict_types=1);

namespace App\Data\V1\Billing;

use App\Models\Payment;
use Spatie\LaravelData\Data;

class PixChargeData extends Data
{
    public function __construct(
        public ?string $txid,
        public ?string $qrcode,        // copia e cola / EMV payload
        public ?string $qrcode_image,  // base64 data URI
        public ?string $provider,
        public ?string $expires_at,
    ) {}

    public static function fromModel(Payment $payment): self
    {
        return new self(
            txid: $payment->pix_txid,
            qrcode: $payment->pix_qrcode,
            qrcode_image: $payment->pix_qrcode_image,
            provider: $payment->pix_provider,
            expires_at: $payment->pix_expires_at?->toIso8601String(),
        );
    }
}
