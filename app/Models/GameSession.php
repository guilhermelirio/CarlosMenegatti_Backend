<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GameSessionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    /** @use HasFactory<GameSessionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'scheduled_date',
        'start_time',
        'location',
        'daily_fee_cents',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'daily_fee_cents' => 'integer',
    ];

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<DailyFee, $this> */
    public function dailyFees(): HasMany
    {
        return $this->hasMany(DailyFee::class);
    }
}
