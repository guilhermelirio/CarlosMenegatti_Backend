<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(TransactionType::cases()),
            'is_system' => false,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Income]);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Expense]);
    }
}
