<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\OrganizationRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user = null): bool => $user !== null
            && $user->organizations()
                ->wherePivot('role', OrganizationRole::Admin->value)
                ->exists());
    }
}
