<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DailyFee;
use App\Models\User;

class DailyFeePolicy
{
    public function view(User $user, DailyFee $fee): bool
    {
        return $user->player !== null && $fee->player_id === $user->player->id;
    }

    public function pay(User $user, DailyFee $fee): bool
    {
        return $this->view($user, $fee);
    }
}
