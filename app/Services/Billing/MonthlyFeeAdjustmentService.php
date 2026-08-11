<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\FeeStatus;
use App\Enums\PaymentStatus;
use App\Models\MonthlyFee;
use App\Services\Audit\AuditService;
use App\Services\Reports\MonthlyClosingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MonthlyFeeAdjustmentService
{
    public function __construct(
        private AuditService $audit,
        private MonthlyClosingService $closings,
    ) {}

    public function applyDiscount(MonthlyFee $fee, int $fixedCents, int $percent): MonthlyFee
    {
        $this->ensureOpen($fee);

        return DB::transaction(function () use ($fee, $fixedCents, $percent): MonthlyFee {
            $before = $fee->attributesToArray();
            $gross = $fee->gross_amount_cents ?? $fee->amount_cents;
            $percent = min(100, max(0, $percent));
            $additionalDiscount = max(0, $fixedCents) + intdiv($gross * $percent, 100);
            $discount = min($gross, $fee->discount_cents + $additionalDiscount);
            $principal = max(0, $gross - $discount);

            $fee->update([
                'discount_cents' => $discount,
                'discount_percent' => min(100, $fee->discount_percent + $percent),
                'amount_cents' => $principal + $fee->late_fee_cents + $fee->interest_cents,
                'status' => $principal === 0 ? FeeStatus::Exempt : $fee->status,
            ]);
            $this->audit->record('monthly_fee_discounted', $fee, $before, $fee->fresh()->attributesToArray());

            return $fee->refresh();
        });
    }

    public function exempt(MonthlyFee $fee): MonthlyFee
    {
        $this->ensureOpen($fee);
        $gross = $fee->gross_amount_cents ?? $fee->amount_cents;

        return DB::transaction(function () use ($fee, $gross): MonthlyFee {
            $before = $fee->attributesToArray();
            $fee->update([
                'discount_cents' => $gross,
                'amount_cents' => 0,
                'late_fee_cents' => 0,
                'interest_cents' => 0,
                'status' => FeeStatus::Exempt,
                'paid_at' => null,
            ]);
            $this->audit->record('monthly_fee_exempted', $fee, $before, $fee->fresh()->attributesToArray());

            return $fee->refresh();
        });
    }

    private function ensureOpen(MonthlyFee $fee): void
    {
        $referenceDate = CarbonImmutable::create($fee->reference_year, $fee->reference_month, 1);

        if ($this->closings->isClosed($referenceDate)) {
            throw ValidationException::withMessages(['fee' => ['O mês está fechado. Reabra-o antes de conceder desconto ou isenção.']]);
        }

        if ($fee->status->isSettled()) {
            throw ValidationException::withMessages(['fee' => ['Cobranças pagas ou isentas não podem receber novos ajustes.']]);
        }

        if ($fee->payments()->where('status', PaymentStatus::Pending)->exists()) {
            throw ValidationException::withMessages(['fee' => ['Cancele a cobrança Pix pendente antes de alterar o valor.']]);
        }
    }
}
