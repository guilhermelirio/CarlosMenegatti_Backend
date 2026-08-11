<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('scheduled_date');
            $table->time('start_time')->nullable();
            $table->string('location')->nullable();
            $table->bigInteger('daily_fee_cents'); // value charged to diaristas for this session
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
