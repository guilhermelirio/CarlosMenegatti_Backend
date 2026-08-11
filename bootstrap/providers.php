<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PixServiceProvider;

return [
    AppServiceProvider::class,
    PixServiceProvider::class,
    HorizonServiceProvider::class,
    AdminPanelProvider::class,
];
