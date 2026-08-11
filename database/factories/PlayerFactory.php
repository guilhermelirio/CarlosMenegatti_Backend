<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipType;
use App\Enums\PlayerPosition;
use App\Enums\PlayerStatus;
use App\Models\Organization;
use App\Models\Player;
use App\Tenancy\CurrentOrganization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(CurrentOrganization::class)->id() ?? Organization::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'nickname' => fake()->optional(0.6)->firstName(),
            'phone' => '(11) 9'.fake()->numerify('####-####'),
            'email' => fake()->optional(0.5)->safeEmail(),
            'position' => fake()->optional(0.8)->randomElement(PlayerPosition::cases()),
            'status' => PlayerStatus::Active,
            'membership_type' => fake()->randomElement(MembershipType::cases()),
            'joined_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'photo_path' => null,
            'monthly_fee_cents' => null,
            'daily_fee_cents' => null,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn () => ['membership_type' => MembershipType::Monthly]);
    }

    public function daily(): static
    {
        return $this->state(fn () => ['membership_type' => MembershipType::Daily]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => PlayerStatus::Inactive]);
    }
}
