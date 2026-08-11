<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\PixServiceProvider;

return [
    AppServiceProvider::class,
    PixServiceProvider::class,
    AdminPanelProvider::class,
];
