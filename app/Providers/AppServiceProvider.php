<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->getAuthIdentifier() ?: $request->ip()
        ));

        // Brute-force protection for login: 5 attempts / 15 min by (IP + email).
        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(15, 5)->by(
            $request->ip().'|'.(string) $request->input('email')
        ));
    }
}
