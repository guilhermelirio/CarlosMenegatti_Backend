<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MonthlyFeeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property FeeStatus $status
 */
class MonthlyFee extends Model
{
    /** @use HasFactory<MonthlyFeeFactory> */
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'player_id',
        'reference_year',
        'reference_month',
        'amount_cents',
        'due_date',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'reference_year' => 'integer',
        'reference_month' => 'integer',
        'amount_cents' => 'integer',
        'due_date' => 'date',
        'status' => FeeStatus::class,
        'paid_at' => 'datetime',
    ];

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function referenceLabel(): string
    {
        return sprintf('%02d/%04d', $this->reference_month, $this->reference_year);
    }
}
