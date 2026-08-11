<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\Session\ConfirmAttendanceData;
use App\Data\V1\Session\GameSessionData;
use App\Models\GameSession;
use App\Services\Attendance\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class GameSessionController extends ApiController
{
    /**
     * Upcoming sessions with the athlete's confirmation status.
     *
     * @return DataCollection<int, GameSessionData>
     */
    public function index(Request $request): DataCollection
    {
        $player = $this->currentPlayer($request);

        $sessions = GameSession::query()
            ->whereDate('scheduled_date', '>=', CarbonImmutable::now()->toDateString())
            ->with(['attendances' => fn ($q) => $q->where('player_id', $player->id)])
            ->orderBy('scheduled_date')
            ->get();

        $data = $sessions->map(function (GameSession $session) {
            $att = $session->attendances->first();

            return GameSessionData::fromModel($session, $att?->confirmed, $att?->attended);
        });

        return GameSessionData::collect($data, DataCollection::class);
    }

    /** Confirm (or un-confirm) the athlete's presence in a session. */
    public function confirm(
        ConfirmAttendanceData $data,
        Request $request,
        GameSession $gameSession,
        AttendanceService $service,
    ): GameSessionData {
        $player = $this->currentPlayer($request);

        $attendance = $service->register(
            $gameSession,
            $player,
            confirmed: $data->confirmed,
            attended: false,
        );

        return GameSessionData::fromModel($gameSession, $attendance->confirmed, $attendance->attended);
    }
}
