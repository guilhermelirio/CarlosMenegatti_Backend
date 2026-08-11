<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Tenancy\CurrentOrganization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationMembership> */
class OrganizationMembershipFactory extends Factory
{
    protected $model = OrganizationMembership::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(CurrentOrganization::class)->id() ?? Organization::factory(),
            'user_id' => User::factory(),
            'role' => OrganizationRole::Member,
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn (): array => ['role' => OrganizationRole::Admin]);
    }
}
