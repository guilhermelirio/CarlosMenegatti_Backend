<?php

declare(strict_types=1);

namespace App\Data\V1\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Money;
use Spatie\LaravelData\Data;

class PaymentData extends Data
{
    public function __construct(
        public string $id,
        public string $payable_type,
        public string $payable_id,
        public int $amount_cents,
        public string $amount_formatted,
        public string $currency,
        public PaymentMethod $method,
        public PaymentStatus $status,
        public ?string $paid_at,
        public bool $has_receipt,
        public ?PixChargeData $pix,
    ) {}

    public static function fromModel(Payment $payment): self
    {
        return new self(
            id: $payment->id,
            payable_type: class_basename($payment->payable_type),
            payable_id: (string) $payment->payable_id,
            amount_cents: $payment->amount_cents,
            amount_formatted: Money::formatBRL($payment->amount_cents),
            currency: 'BRL',
            method: $payment->method,
            status: $payment->status,
            paid_at: $payment->paid_at?->toIso8601String(),
            has_receipt: $payment->receipt_path !== null,
            pix: $payment->method === PaymentMethod::Pix && $payment->pix_qrcode !== null
                ? PixChargeData::fromModel($payment)
                : null,
        );
    }
}
