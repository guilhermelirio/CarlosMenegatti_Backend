<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DailyFeeController;
use App\Http\Controllers\Api\V1\GameSessionController;
use App\Http\Controllers\Api\V1\MonthlyFeeController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Webhooks\PixWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // --- Public ---
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    // --- Authenticated athlete (Sanctum) ---
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::get('me/monthly-fees', [MonthlyFeeController::class, 'index']);
        Route::get('me/daily-fees', [DailyFeeController::class, 'index']);
        Route::get('me/payments', [PaymentController::class, 'index']);

        Route::get('sessions', [GameSessionController::class, 'index']);
        Route::post('sessions/{gameSession}/confirm', [GameSessionController::class, 'confirm']);

        Route::post('monthly-fees/{monthlyFee}/pix', [MonthlyFeeController::class, 'pix']);
        Route::post('daily-fees/{dailyFee}/pix', [DailyFeeController::class, 'pix']);

        Route::get('payments/{payment}', [PaymentController::class, 'show']);
    });
});

// --- Inbound webhooks (auth by signature, no Sanctum/CSRF, not rate-limited) ---
Route::post('webhooks/pix/{provider}/{secret}', PixWebhookController::class);
