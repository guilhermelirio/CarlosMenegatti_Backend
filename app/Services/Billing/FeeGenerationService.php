<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\FeeStatus;
use App\Enums\MembershipType;
use App\Enums\PlayerStatus;
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
                    $fee = MonthlyFee::query()->firstOrCreate(
                        [
                            'player_id' => $player->id,
                            'reference_year' => $year,
                            'reference_month' => $month,
                        ],
                        [
                            'amount_cents' => $player->effectiveMonthlyFeeCents(),
                            'due_date' => $dueDate->toDateString(),
                            'status' => FeeStatus::Pending,
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

        return MonthlyFee::query()
            ->where('status', FeeStatus::Pending)
            ->whereDate('due_date', '<', $today->toDateString())
            ->update(['status' => FeeStatus::Overdue]);
    }
}
