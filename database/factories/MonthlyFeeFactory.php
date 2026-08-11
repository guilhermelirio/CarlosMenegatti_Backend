<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeeStatus;
use App\Models\MonthlyFee;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyFee>
 */
class MonthlyFeeFactory extends Factory
{
    protected $model = MonthlyFee::class;

    public function definition(): array
    {
        $year = (int) fake()->randomElement([2025, 2026]);
        $month = fake()->numberBetween(1, 12);

        return [
            'player_id' => Player::factory()->monthly(),
            'reference_year' => $year,
            'reference_month' => $month,
            'amount_cents' => 5000,
            'due_date' => sprintf('%04d-%02d-10', $year, $month),
            'status' => FeeStatus::Pending,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => FeeStatus::Paid, 'paid_at' => now()]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['status' => FeeStatus::Overdue]);
    }
}
