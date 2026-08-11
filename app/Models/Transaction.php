<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property TransactionType $type
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'type',
        'category_id',
        'player_id',
        'payment_id',
        'reversal_of_id',
        'amount_cents',
        'occurred_on',
        'description',
        'receipt_path',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'amount_cents' => 'integer',
        'occurred_on' => 'date',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<Transaction, $this> */
    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /** @return HasMany<Transaction, $this> */
    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }
}
