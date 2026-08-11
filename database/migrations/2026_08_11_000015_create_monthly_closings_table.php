<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_closings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('reference_year');
            $table->unsignedTinyInteger('reference_month');
            $table->boolean('is_closed')->default(true);
            $table->json('snapshot');
            $table->timestampTz('closed_at');
            $table->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reopen_reason')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'reference_year', 'reference_month']);
            $table->index(['organization_id', 'is_closed', 'reference_year', 'reference_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_closings');
    }
};
