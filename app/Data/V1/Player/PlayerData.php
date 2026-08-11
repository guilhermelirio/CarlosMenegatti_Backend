<?php

declare(strict_types=1);

namespace App\Data\V1\Player;

use App\Enums\MembershipType;
use App\Enums\PlayerStatus;
use App\Models\Player;
use Spatie\LaravelData\Data;

class PlayerData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $nickname,
        public ?string $phone,
        public ?string $email,
        public ?string $position,
        public PlayerStatus $status,
        public MembershipType $membership_type,
        public bool $is_permanently_exempt,
        public int $monthly_discount_cents,
        public int $monthly_discount_percent,
        public ?string $joined_at,
    ) {}

    public static function fromModel(Player $player): self
    {
        return new self(
            id: $player->id,
            name: $player->name,
            nickname: $player->nickname,
            phone: $player->phone,
            email: $player->email,
            position: $player->position?->label(),
            status: $player->status,
            membership_type: $player->membership_type,
            is_permanently_exempt: $player->is_permanently_exempt,
            monthly_discount_cents: $player->monthly_discount_cents,
            monthly_discount_percent: $player->monthly_discount_percent,
            joined_at: $player->joined_at?->toDateString(),
        );
    }
}
