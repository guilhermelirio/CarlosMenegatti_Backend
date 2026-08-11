<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeStatus;
use Database\Factories\DailyFeeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property FeeStatus $status
 */
class DailyFee extends Model
{
    /** @use HasFactory<DailyFeeFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'player_id',
        'game_session_id',
        'amount_cents',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'status' => FeeStatus::class,
        'paid_at' => 'datetime',
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

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
