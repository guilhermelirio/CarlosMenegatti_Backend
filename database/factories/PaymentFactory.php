<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\MonthlyFee;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $fee = MonthlyFee::factory()->create();

        return [
            'player_id' => $fee->player_id,
            'payable_type' => $fee->getMorphClass(),
            'payable_id' => $fee->id,
            'amount_cents' => $fee->amount_cents,
            'method' => PaymentMethod::Pix,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::Confirmed, 'paid_at' => now()]);
    }
}
