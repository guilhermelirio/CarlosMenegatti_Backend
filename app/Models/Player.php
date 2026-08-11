<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipType;
use App\Enums\PlayerPosition;
use App\Enums\PlayerStatus;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property PlayerStatus $status
 * @property MembershipType $membership_type
 * @property ?PlayerPosition $position
 */
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'nickname',
        'phone',
        'email',
        'position',
        'status',
        'membership_type',
        'joined_at',
        'photo_path',
        'monthly_fee_cents',
        'daily_fee_cents',
        'notes',
    ];

    protected $casts = [
        'status' => PlayerStatus::class,
        'membership_type' => MembershipType::class,
        'position' => PlayerPosition::class,
        'joined_at' => 'date',
        'monthly_fee_cents' => 'integer',
        'daily_fee_cents' => 'integer',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<MonthlyFee, $this> */
    public function monthlyFees(): HasMany
    {
        return $this->hasMany(MonthlyFee::class);
    }

    /** @return HasMany<DailyFee, $this> */
    public function dailyFees(): HasMany
    {
        return $this->hasMany(DailyFee::class);
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isMonthly(): bool
    {
        return $this->membership_type === MembershipType::Monthly;
    }

    /** Effective monthly fee: individual override or the configured default. */
    public function effectiveMonthlyFeeCents(): int
    {
        return $this->monthly_fee_cents
            ?? Setting::getInt(Setting::DEFAULT_MONTHLY_FEE_CENTS);
    }

    /** Effective daily fee: individual override or the configured default. */
    public function effectiveDailyFeeCents(): int
    {
        return $this->daily_fee_cents
            ?? Setting::getInt(Setting::DEFAULT_DAILY_FEE_CENTS);
    }
}
