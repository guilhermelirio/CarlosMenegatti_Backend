<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate this month's monthly fees on the 1st, then flag overdue ones daily.
Schedule::command('fees:generate-monthly')->monthlyOn(1, '06:00');
Schedule::command('fees:mark-overdue')->dailyAt('06:30');
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Keep database and private receipts backed up daily.
Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->timezone('America/Sao_Paulo')
    ->onOneServer()
    ->withoutOverlapping(180);
Schedule::command('backup:run')
    ->dailyAt('03:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer()
    ->withoutOverlapping(180);
Schedule::command('backup:monitor')
    ->dailyAt('04:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer()
    ->withoutOverlapping(60);
