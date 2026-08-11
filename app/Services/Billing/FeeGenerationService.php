<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\FeeStatus;
use App\Enums\MembershipType;
use App\Enums\PlayerStatus;
use App\Models\DailyFee;
use App\Models\MonthlyFee;
use App\Models\Player;
use App\Models\Setting;
use Carbon\CarbonImmutable;

final class FeeGenerationService
{
    /**
     * Generate monthly fees for every active monthly-member player for a given
     * competência (year/month). Idempotent: existing fees are skipped.
     *
     * @return int number of fees created
     */
    public function generateForMonth(int $year, int $month): int
    {
        $dueDay = max(1, min(28, Setting::getInt(Setting::MONTHLY_FEE_DUE_DAY, 10)));
        $dueDate = CarbonImmutable::create($year, $month, $dueDay);

        $created = 0;

        Player::query()
            ->where('membership_type', MembershipType::Monthly)
            ->where('status', PlayerStatus::Active)
            ->chunkById(100, function ($players) use ($year, $month, $dueDate, &$created): void {
                foreach ($players as $player) {
                    $grossAmount = $player->effectiveMonthlyFeeCents();
                    $discountCents = $player->is_permanently_exempt
                        ? $grossAmount
                        : $player->effectiveMonthlyDiscountCents();
                    $fee = MonthlyFee::query()->firstOrCreate(
                        [
                            'player_id' => $player->id,
                            'reference_year' => $year,
                            'reference_month' => $month,
                        ],
                        [
                            'amount_cents' => max(0, $grossAmount - $discountCents),
                            'gross_amount_cents' => $grossAmount,
                            'discount_cents' => $discountCents,
                            'discount_percent' => $player->monthly_discount_percent,
                            'late_fee_cents' => 0,
                            'interest_cents' => 0,
                            'due_date' => $dueDate->toDateString(),
                            'status' => $player->is_permanently_exempt ? FeeStatus::Exempt : FeeStatus::Pending,
                        ],
                    );

                    if ($fee->wasRecentlyCreated) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    /**
     * Flag pending monthly fees whose due date has passed as overdue.
     *
     * @return int number of fees updated
     */
    public function markOverdue(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::now();

        $lateFeePercent = min(100, max(0, Setting::getInt(Setting::LATE_FEE_PERCENT)));
        $monthlyInterestPercent = min(100, max(0, Setting::getInt(Setting::MONTHLY_INTEREST_PERCENT)));
        $updated = 0;

        MonthlyFee::query()
            ->whereIn('status', [FeeStatus::Pending, FeeStatus::Overdue])
            ->whereDate('due_date', '<', $today->toDateString())
            ->each(function (MonthlyFee $fee) use ($today, $lateFeePercent, $monthlyInterestPercent, &$updated): void {
                $principal = $fee->principalAmountCents();
                $monthsLate = max(0, (int) $fee->due_date->startOfMonth()->diffInMonths($today->startOfMonth()));
                $lateFee = intdiv($principal * $lateFeePercent, 100);
                $interest = intdiv($principal * $monthlyInterestPercent * $monthsLate, 100);

                $fee->update([
                    'status' => FeeStatus::Overdue,
                    'late_fee_cents' => $lateFee,
                    'interest_cents' => $interest,
                    'amount_cents' => $principal + $lateFee + $interest,
                ]);
                $updated++;
            });

        $updated += DailyFee::query()
            ->where('status', FeeStatus::Pending)
            ->whereHas('gameSession', fn ($query) => $query->whereDate('scheduled_date', '<', $today->toDateString()))
            ->update(['status' => FeeStatus::Overdue]);

        return $updated;
    }
}
