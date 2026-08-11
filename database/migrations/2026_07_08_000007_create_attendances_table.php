<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('player_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('game_session_id')->constrained()->cascadeOnDelete();
            $table->boolean('confirmed')->default(false);
            $table->boolean('attended')->default(false);
            $table->foreignUlid('daily_fee_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['player_id', 'game_session_id']);
            $table->index(['game_session_id', 'attended']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
