<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_players')->default(20)->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->dropColumn('max_players');
        });
    }
};
