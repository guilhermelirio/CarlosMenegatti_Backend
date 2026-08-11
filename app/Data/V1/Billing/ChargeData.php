<?php

declare(strict_types=1);

namespace App\Data\V1\Billing;

use App\Enums\FeeStatus;
use App\Models\Charge;
use App\Models\DailyFee;
use App\Models\MonthlyFee;
use App\Support\Money;
use Spatie\LaravelData\Data;

class ChargeData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $type_label,
        public string $reference_label,
        public string $reference_date,
        public int $amount_cents,
        public string $amount_formatted,
        public string $currency,
        public FeeStatus $status,
        public bool $can_pay,
        public ?string $paid_at,
        public ?string $due_date,
        public ?string $game_id,
        public ?string $game_date,
        public ?int $gross_amount_cents,
        public ?int $discount_cents,
        public ?int $discount_percent,
        public ?int $late_fee_cents,
        public ?int $interest_cents,
    ) {}

    public static function fromModels(Charge $charge, MonthlyFee|DailyFee $underlying): self
    {
        $monthlyFee = $underlying instanceof MonthlyFee ? $underlying : null;
        $dailyFee = $underlying instanceof DailyFee ? $underlying : null;

        return new self(
            id: $charge->id,
            type: $charge->charge_type,
            type_label: $charge->charge_type === 'monthly' ? 'Mensalidade' : 'Diária',
            reference_label: $charge->reference_label,
            reference_date: $charge->reference_date->toDateString(),
            amount_cents: $charge->amount_cents,
            amount_formatted: Money::formatBRL($charge->amount_cents),
            currency: 'BRL',
            status: $charge->status,
            can_pay: ! $charge->status->isSettled(),
            paid_at: $charge->paid_at?->toIso8601String(),
            due_date: $monthlyFee?->due_date->toDateString(),
            game_id: $dailyFee?->game_session_id,
            game_date: $dailyFee?->gameSession?->scheduled_date?->toDateString(),
            gross_amount_cents: $monthlyFee?->gross_amount_cents,
            discount_cents: $monthlyFee?->discount_cents,
            discount_percent: $monthlyFee?->discount_percent,
            late_fee_cents: $monthlyFee?->late_fee_cents,
            interest_cents: $monthlyFee?->interest_cents,
        );
    }
}
