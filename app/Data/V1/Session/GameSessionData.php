<?php

declare(strict_types=1);

namespace App\Data\V1\Session;

use App\Models\GameSession;
use App\Support\Money;
use Spatie\LaravelData\Data;

class GameSessionData extends Data
{
    public function __construct(
        public string $id,
        public string $scheduled_date,
        public ?string $start_time,
        public ?string $location,
        public int $daily_fee_cents,
        public string $daily_fee_formatted,
        public ?string $notes,
        public ?bool $confirmed,  // whether the current player confirmed (when known)
        public ?bool $attended,
    ) {}

    public static function fromModel(GameSession $session, ?bool $confirmed = null, ?bool $attended = null): self
    {
        return new self(
            id: $session->id,
            scheduled_date: $session->scheduled_date->toDateString(),
            start_time: $session->start_time,
            location: $session->location,
            daily_fee_cents: $session->daily_fee_cents,
            daily_fee_formatted: Money::formatBRL($session->daily_fee_cents),
            notes: $session->notes,
            confirmed: $confirmed,
            attended: $attended,
        );
    }
}
