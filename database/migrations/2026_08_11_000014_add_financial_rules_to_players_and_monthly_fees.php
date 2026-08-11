<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table): void {
            $table->boolean('is_permanently_exempt')->default(false)->after('daily_fee_cents');
            $table->bigInteger('monthly_discount_cents')->default(0)->after('is_permanently_exempt');
            $table->unsignedTinyInteger('monthly_discount_percent')->default(0)->after('monthly_discount_cents');
        });

        Schema::table('monthly_fees', function (Blueprint $table): void {
            $table->bigInteger('gross_amount_cents')->nullable()->after('amount_cents');
            $table->bigInteger('discount_cents')->default(0)->after('gross_amount_cents');
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('discount_cents');
            $table->bigInteger('late_fee_cents')->default(0)->after('discount_percent');
            $table->bigInteger('interest_cents')->default(0)->after('late_fee_cents');
        });

        DB::table('monthly_fees')->update([
            'gross_amount_cents' => DB::raw('amount_cents'),
        ]);
    }

    public function down(): void
    {
        Schema::table('monthly_fees', function (Blueprint $table): void {
            $table->dropColumn([
                'gross_amount_cents',
                'discount_cents',
                'discount_percent',
                'late_fee_cents',
                'interest_cents',
            ]);
        });

        Schema::table('players', function (Blueprint $table): void {
            $table->dropColumn([
                'is_permanently_exempt',
                'monthly_discount_cents',
                'monthly_discount_percent',
            ]);
        });
    }
};
