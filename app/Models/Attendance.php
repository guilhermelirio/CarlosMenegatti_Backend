<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\AttendanceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(AttendanceObserver::class)]
class Attendance extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'player_id',
        'game_session_id',
        'confirmed',
        'attended',
        'daily_fee_id',
    ];

    protected $casts = [
        'confirmed' => 'boolean',
        'attended' => 'boolean',
    ];

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<GameSession, $this> */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /** @return BelongsTo<DailyFee, $this> */
    public function dailyFee(): BelongsTo
    {
        return $this->belongsTo(DailyFee::class);
    }
}
