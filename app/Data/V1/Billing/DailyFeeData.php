<?php

declare(strict_types=1);

namespace App\Data\V1\Billing;

use App\Enums\FeeStatus;
use App\Models\DailyFee;
use App\Support\Money;
use Spatie\LaravelData\Data;

class DailyFeeData extends Data
{
    public function __construct(
        public string $id,
        public string $game_session_id,
        public ?string $session_date,
        public int $amount_cents,
        public string $amount_formatted,
        public string $currency,
        public FeeStatus $status,
        public ?string $paid_at,
    ) {}

    public static function fromModel(DailyFee $fee): self
    {
        return new self(
            id: $fee->id,
            game_session_id: $fee->game_session_id,
            session_date: $fee->gameSession?->scheduled_date?->toDateString(),
            amount_cents: $fee->amount_cents,
            amount_formatted: Money::formatBRL($fee->amount_cents),
            currency: 'BRL',
            status: $fee->status,
            paid_at: $fee->paid_at?->toIso8601String(),
        );
    }
}
