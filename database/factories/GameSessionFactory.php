<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    protected $model = GameSession::class;

    public function definition(): array
    {
        return [
            'scheduled_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'start_time' => fake()->randomElement(['19:00', '19:30', '20:00', '20:30']),
            'location' => fake()->randomElement(['Quadra do Zé', 'Society Bola na Rede', 'Campo do Bairro']),
            'daily_fee_cents' => Setting::getInt(Setting::DEFAULT_DAILY_FEE_CENTS, 2000),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
