<?php

declare(strict_types=1);

namespace App\Data\V1\Auth;

use App\Data\V1\Player\PlayerData;
use Spatie\LaravelData\Data;

class AuthTokenData extends Data
{
    public function __construct(
        public string $token,
        public string $token_type,
        public PlayerData $player,
    ) {}
}
