<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DailyFee;
use App\Models\Player;
use App\Models\User;

class DailyFeePolicy
{
    public function view(User $user, DailyFee $fee): bool
    {
        $player = $user->players()->first();

        return $player instanceof Player && $fee->player_id === $player->getKey();
    }

    public function pay(User $user, DailyFee $fee): bool
    {
        return $this->view($user, $fee);
    }
}
