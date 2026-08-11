<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\FeeStatus;
use App\Models\Attendance;
use App\Models\DailyFee;
use App\Models\GameSession;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttendanceService
{
    /** Create or update a player's attendance for a session. */
    public function register(GameSession $session, Player $player, bool $confirmed, bool $attended): Attendance
    {
        return DB::transaction(function () use ($session, $player, $confirmed, $attended): Attendance {
            $lockedSession = GameSession::query()->lockForUpdate()->findOrFail($session->id);
            $existing = Attendance::query()
                ->where('game_session_id', $lockedSession->id)
                ->where('player_id', $player->id)
                ->first();
            $alreadyConfirmed = $existing !== null && $existing->confirmed;

            if ($confirmed && ! $alreadyConfirmed) {
                $confirmedCount = Attendance::query()
                    ->where('game_session_id', $lockedSession->id)
                    ->where('confirmed', true)
                    ->count();

                if ($confirmedCount >= $lockedSession->max_players) {
                    throw ValidationException::withMessages([
                        'confirmed' => ['O limite de jogadores deste jogo já foi atingido.'],
                    ]);
                }
            }

            return Attendance::query()->updateOrCreate(
                ['game_session_id' => $lockedSession->id, 'player_id' => $player->id],
                ['confirmed' => $confirmed, 'attended' => $attended],
            );
        });
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
