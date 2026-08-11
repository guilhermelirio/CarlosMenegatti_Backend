<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipType;
use App\Enums\PlayerPosition;
use App\Enums\PlayerStatus;
use App\Models\Concerns\BelongsToOrganization;
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
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

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
        'is_permanently_exempt',
        'monthly_discount_cents',
        'monthly_discount_percent',
        'notes',
    ];

    protected $casts = [
        'status' => PlayerStatus::class,
        'membership_type' => MembershipType::class,
        'position' => PlayerPosition::class,
        'joined_at' => 'date',
        'monthly_fee_cents' => 'integer',
        'daily_fee_cents' => 'integer',
        'is_permanently_exempt' => 'boolean',
        'monthly_discount_cents' => 'integer',
        'monthly_discount_percent' => 'integer',
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

    public function isGuest(): bool
    {
        return $this->membership_type === MembershipType::Guest;
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

    public function effectiveMonthlyDiscountCents(): int
    {
        $gross = $this->effectiveMonthlyFeeCents();
        $percent = min(100, max(0, $this->monthly_discount_percent));
        $percentageDiscount = intdiv($gross * $percent, 100);

        return min($gross, $percentageDiscount + max(0, $this->monthly_discount_cents));
    }

    protected static function booted(): void
    {
        static::saving(function (Player $player): void {
            if ($player->membership_type === MembershipType::Guest) {
                $player->user_id = null;
            }
        });
    }
}
