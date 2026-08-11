<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeeStatus;
use App\Models\DailyFee;
use App\Models\GameSession;
use App\Models\Organization;
use App\Models\Player;
use App\Tenancy\CurrentOrganization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyFee>
 */
class DailyFeeFactory extends Factory
{
    protected $model = DailyFee::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(CurrentOrganization::class)->id() ?? Organization::factory(),
            'player_id' => Player::factory()->daily(),
            'game_session_id' => GameSession::factory(),
            'amount_cents' => 2000,
            'status' => FeeStatus::Pending,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => FeeStatus::Paid, 'paid_at' => now()]);
    }
}
