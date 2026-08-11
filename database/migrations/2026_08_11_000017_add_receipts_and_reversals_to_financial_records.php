<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignUlid('reversal_of_id')->nullable()->after('payment_id')->constrained('transactions')->nullOnDelete();
            $table->string('receipt_path')->nullable()->after('description');
            $table->softDeletesTz();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('receipt_path')->nullable()->after('metadata');
            $table->timestampTz('receipt_uploaded_at')->nullable()->after('receipt_path');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['receipt_path', 'receipt_uploaded_at']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reversal_of_id');
            $table->dropColumn(['receipt_path', 'deleted_at']);
        });
    }
};
