<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_fees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('player_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('game_session_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount_cents');
            $table->string('status')->default('pending'); // FeeStatus (Pending/Paid/Exempt)
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();

            $table->unique(['player_id', 'game_session_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_fees');
    }
};
