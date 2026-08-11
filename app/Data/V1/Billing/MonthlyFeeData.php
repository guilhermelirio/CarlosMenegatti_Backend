<?php

declare(strict_types=1);

namespace App\Data\V1\Billing;

use App\Enums\FeeStatus;
use App\Models\MonthlyFee;
use App\Support\Money;
use Spatie\LaravelData\Data;

class MonthlyFeeData extends Data
{
    public function __construct(
        public string $id,
        public int $reference_year,
        public int $reference_month,
        public string $reference_label,
        public int $amount_cents,
        public string $amount_formatted,
        public string $currency,
        public string $due_date,
        public FeeStatus $status,
        public ?string $paid_at,
    ) {}

    public static function fromModel(MonthlyFee $fee): self
    {
        return new self(
            id: $fee->id,
            reference_year: $fee->reference_year,
            reference_month: $fee->reference_month,
            reference_label: $fee->referenceLabel(),
            amount_cents: $fee->amount_cents,
            amount_formatted: Money::formatBRL($fee->amount_cents),
            currency: 'BRL',
            due_date: $fee->due_date->toDateString(),
            status: $fee->status,
            paid_at: $fee->paid_at?->toIso8601String(),
        );
    }
}
