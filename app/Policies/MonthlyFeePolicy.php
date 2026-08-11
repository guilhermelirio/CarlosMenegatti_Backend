<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MonthlyFee;
use App\Models\User;

class MonthlyFeePolicy
{
    public function view(User $user, MonthlyFee $fee): bool
    {
        return $user->player !== null && $fee->player_id === $user->player->id;
    }

    public function pay(User $user, MonthlyFee $fee): bool
    {
        return $this->view($user, $fee);
    }
}
