<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integrations\Pix\Contracts\PixGatewayContract;
use App\Integrations\Pix\PixManager;
use Illuminate\Support\ServiceProvider;

class PixServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PixManager::class);

        $this->app->bind(
            PixGatewayContract::class,
            fn ($app) => $app->make(PixManager::class)->driver(),
        );
    }
}
