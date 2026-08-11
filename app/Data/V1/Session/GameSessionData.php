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
        public int $max_players,
        public int $confirmed_count,
        public int $available_spots,
        public bool $is_full,
        public int $daily_fee_cents,
        public string $daily_fee_formatted,
        public ?string $notes,
        public ?bool $confirmed,  // whether the current player confirmed (when known)
        public ?bool $attended,
    ) {}

    public static function fromModel(GameSession $session, ?bool $confirmed = null, ?bool $attended = null): self
    {
        $confirmedCount = (int) ($session->getAttribute('confirmed_count')
            ?? $session->attendances()->where('confirmed', true)->count());
        $availableSpots = max(0, $session->max_players - $confirmedCount);

        return new self(
            id: $session->id,
            scheduled_date: $session->scheduled_date->toDateString(),
            start_time: $session->start_time,
            location: $session->location,
            max_players: $session->max_players,
            confirmed_count: $confirmedCount,
            available_spots: $availableSpots,
            is_full: $availableSpots === 0,
            daily_fee_cents: $session->daily_fee_cents,
            daily_fee_formatted: Money::formatBRL($session->daily_fee_cents),
            notes: $session->notes,
            confirmed: $confirmed,
            attended: $attended,
        );
    }
}
