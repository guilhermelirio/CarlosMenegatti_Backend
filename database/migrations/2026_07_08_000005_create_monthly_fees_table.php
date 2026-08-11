<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_fees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('reference_year');
            $table->unsignedTinyInteger('reference_month');
            $table->bigInteger('amount_cents');
            $table->date('due_date');
            $table->string('status')->default('pending'); // FeeStatus
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();

            $table->unique(['player_id', 'reference_year', 'reference_month']);
            $table->index(['organization_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_fees');
    }
};
