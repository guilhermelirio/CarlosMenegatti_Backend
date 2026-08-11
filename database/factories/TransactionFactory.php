<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = fake()->randomElement(TransactionType::cases());

        return [
            'type' => $type,
            'category_id' => Category::factory()->state(['type' => $type]),
            'player_id' => null,
            'payment_id' => null,
            'amount_cents' => fake()->numberBetween(2000, 50000),
            'occurred_on' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'description' => fake()->optional()->sentence(4),
        ];
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Expense]);
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Income]);
    }
}
