<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function create(User $user): bool
    {
        return $user->is_staff || $user->organizations()
            ->wherePivot('role', OrganizationRole::Admin->value)
            ->exists();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->organizations()
            ->whereKey($organization->getKey())
            ->wherePivot('role', OrganizationRole::Admin->value)
            ->exists();
    }
}
