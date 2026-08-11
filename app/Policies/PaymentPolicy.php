<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\Player;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        $player = $user->players()->first();

        return $player instanceof Player && $payment->player_id === $player->getKey();
    }
}
