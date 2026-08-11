<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('player_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('payable'); // monthly_fee or daily_fee
            $table->bigInteger('amount_cents');
            $table->string('method');  // PaymentMethod
            $table->string('status')->default('pending'); // PaymentStatus
            $table->timestampTz('paid_at')->nullable();

            // Pix fields
            $table->string('pix_txid')->nullable()->index();
            $table->text('pix_qrcode')->nullable();       // copia-e-cola / EMV payload
            $table->text('pix_qrcode_image')->nullable(); // base64 image (data URI)
            $table->string('pix_provider')->nullable();
            $table->timestampTz('pix_expires_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'status', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
