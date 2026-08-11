<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MonthlyFee;
use App\Models\Player;
use App\Models\User;

class MonthlyFeePolicy
{
    public function view(User $user, MonthlyFee $fee): bool
    {
        $player = $user->players()->first();

        return $player instanceof Player && $fee->player_id === $player->getKey();
    }

    public function pay(User $user, MonthlyFee $fee): bool
    {
        return $this->view($user, $fee);
    }
}
