<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeStatus;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cobrança unificada (mensalidade OU diária). Mapeada na VIEW `charges` —
 * SOMENTE LEITURA. Ações de baixa resolvem o modelo real via underlying().
 *
 * @property string $id
 * @property string $charge_type 'monthly' | 'daily'
 * @property string $player_id
 * @property int $amount_cents
 * @property FeeStatus $status
 * @property string $reference_label
 * @property CarbonInterface $reference_date
 * @property CarbonInterface|null $paid_at
 */
class Charge extends Model
{
    use BelongsToOrganization;

    protected $table = 'charges';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $casts = [
        'amount_cents' => 'integer',
        'status' => FeeStatus::class,
        'reference_date' => 'date',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** Resolve o modelo real por trás desta cobrança (para dar baixa). */
    public function underlying(): MonthlyFee|DailyFee|null
    {
        return $this->charge_type === 'monthly'
            ? MonthlyFee::find($this->id)
            : DailyFee::find($this->id);
    }
}
