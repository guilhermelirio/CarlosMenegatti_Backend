<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\OrganizationRole;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\TransactionObserver;
use App\Tenancy\CurrentOrganization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentOrganization::class);
    }

    public function boot(): void
    {
        Transaction::observe(TransactionObserver::class);

        Gate::define('viewPulse', fn (?User $user = null): bool => $user !== null
            && $user->organizations()
                ->wherePivot('role', OrganizationRole::Admin->value)
                ->exists());

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->getAuthIdentifier() ?: $request->ip()
        ));

        // Brute-force protection for login: 5 attempts / 15 min by (IP + email).
        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(15, 5)->by(
            $request->ip().'|'.(string) $request->input('email')
        ));
    }
}
