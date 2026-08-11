<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property string $payable_type
 * @property string $payable_id
 * @property string $player_id
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'player_id',
        'payable_type',
        'payable_id',
        'amount_cents',
        'method',
        'status',
        'paid_at',
        'pix_txid',
        'pix_qrcode',
        'pix_qrcode_image',
        'pix_provider',
        'pix_expires_at',
        'metadata',
        'receipt_path',
        'receipt_uploaded_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'pix_expires_at' => 'datetime',
        'metadata' => 'array',
        'receipt_uploaded_at' => 'datetime',
    ];

    /** @return MorphTo<Model, $this> */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return HasOne<Transaction, $this> */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
