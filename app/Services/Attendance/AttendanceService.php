<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\FeeStatus;
use App\Models\Attendance;
use App\Models\DailyFee;
use App\Models\GameSession;
use App\Models\Player;

final class AttendanceService
{
    /** Create or update a player's attendance for a session. */
    public function register(GameSession $session, Player $player, bool $confirmed, bool $attended): Attendance
    {
        return Attendance::query()->updateOrCreate(
            ['game_session_id' => $session->id, 'player_id' => $player->id],
            ['confirmed' => $confirmed, 'attended' => $attended],
        );
    }

    /**
     * Keep the diarista's daily fee in sync with an attendance record.
     * - Diarista attended and no fee yet -> create a pending daily fee and link it.
     * - Attendance no longer counts as attended and the fee is still pending -> remove it.
     * Called from the AttendanceObserver; writes to the attendance quietly to avoid loops.
     */
    public function syncDailyFee(Attendance $attendance): void
    {
        $player = $attendance->player;

        if ($player === null || $player->isMonthly()) {
            return; // monthly members never generate a daily fee
        }

        if ($attendance->attended) {
            if ($attendance->daily_fee_id !== null) {
                return;
            }

            $fee = DailyFee::query()->firstOrCreate(
                [
                    'player_id' => $player->id,
                    'game_session_id' => $attendance->game_session_id,
                ],
                [
                    'amount_cents' => $this->sessionDailyFeeCents($attendance, $player),
                    'status' => FeeStatus::Pending,
                ],
            );

            $attendance->daily_fee_id = $fee->id;
            $attendance->saveQuietly();

            return;
        }

        // Not attended: drop the linked daily fee if it is still pending.
        if ($attendance->daily_fee_id !== null) {
            $fee = DailyFee::query()->find($attendance->daily_fee_id);

            if ($fee !== null && $fee->status === FeeStatus::Pending) {
                $fee->delete();
            }

            $attendance->daily_fee_id = null;
            $attendance->saveQuietly();
        }
    }

    private function sessionDailyFeeCents(Attendance $attendance, Player $player): int
    {
        $session = $attendance->gameSession;

        if ($session !== null && $session->daily_fee_cents > 0) {
            return $session->daily_fee_cents;
        }

        return $player->effectiveDailyFeeCents();
    }
}
