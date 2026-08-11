<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\FeeStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Integrations\Pix\Contracts\PixGatewayContract;
use App\Integrations\Pix\Dto\PixChargeRequest;
use App\Models\DailyFee;
use App\Models\MonthlyFee;
use App\Models\Payment;
use App\Services\CashFlow\CashFlowService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class PaymentService
{
    public function __construct(
        private PixGatewayContract $gateway,
        private CashFlowService $cashFlow,
    ) {}

    /**
     * Start a Pix payment for a MonthlyFee or DailyFee. Idempotent per payable:
     * an existing pending Pix charge is returned instead of creating a new one.
     *
     * @param  MonthlyFee|DailyFee  $payable
     */
    public function initiatePix(Model $payable): Payment
    {
        $this->assertPayable($payable);

        if ($payable->status->isSettled()) {
            throw new RuntimeException('Este lançamento já está quitado.');
        }

        $existing = $payable->payments()
            ->where('method', PaymentMethod::Pix)
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($payable): Payment {
            /** @var Payment $payment */
            $payment = $payable->payments()->make([
                'player_id' => $payable->player_id,
                'amount_cents' => $payable->amount_cents,
                'method' => PaymentMethod::Pix,
                'status' => PaymentStatus::Pending,
            ]);
            $payment->save();

            $charge = $this->gateway->createCharge(new PixChargeRequest(
                amountCents: $payable->amount_cents,
                payerName: $payable->player->name,
                description: $this->describe($payable),
                referenceId: $payment->id,
                expiresInSeconds: (int) config('pix.expires_in_seconds', 3600),
            ));

            $payment->forceFill([
                'pix_txid' => $charge->txid,
                'pix_qrcode' => $charge->qrCodePayload,
                'pix_qrcode_image' => $charge->qrCodeImage,
                'pix_provider' => $charge->provider,
                'pix_expires_at' => $charge->expiresAt,
            ])->save();

            return $payment;
        });
    }

    /**
     * Register a manual (cash/transfer) payment for a payable and settle it.
     *
     * @param  MonthlyFee|DailyFee  $payable
     */
    public function registerManualPayment(Model $payable, PaymentMethod $method): Payment
    {
        $this->assertPayable($payable);

        return DB::transaction(function () use ($payable, $method): Payment {
            /** @var Payment $payment */
            $payment = $payable->payments()->make([
                'player_id' => $payable->player_id,
                'amount_cents' => $payable->amount_cents,
                'method' => $method,
                'status' => PaymentStatus::Pending,
            ]);
            $payment->save();

            $this->confirm($payment);

            return $payment;
        });
    }

    /**
     * Confirm a payment: mark it confirmed, settle the underlying fee and post the
     * income into the cash flow. Idempotent — a confirmed payment is a no-op.
     */
    public function confirm(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::Confirmed) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $now = CarbonImmutable::now();

            $payment->forceFill([
                'status' => PaymentStatus::Confirmed,
                'paid_at' => $now,
            ])->save();

            /** @var MonthlyFee|DailyFee|null $payable */
            $payable = $payment->payable;

            if ($payable !== null && ! $payable->status->isSettled()) {
                $payable->forceFill([
                    'status' => FeeStatus::Paid,
                    'paid_at' => $now,
                ])->save();
            }

            $this->cashFlow->recordFromPayment(
                $payment,
                $this->categoryName($payable),
                $this->describe($payable),
            );
        });
    }

    private function assertPayable(Model $payable): void
    {
        if (! $payable instanceof MonthlyFee && ! $payable instanceof DailyFee) {
            throw new RuntimeException('Payable must be a MonthlyFee or DailyFee.');
        }
    }

    private function categoryName(?Model $payable): string
    {
        return $payable instanceof MonthlyFee ? 'Mensalidade' : 'Diária';
    }

    private function describe(?Model $payable): string
    {
        if ($payable instanceof MonthlyFee) {
            return "Mensalidade {$payable->referenceLabel()} - {$payable->player->name}";
        }

        if ($payable instanceof DailyFee) {
            $date = optional($payable->gameSession)->scheduled_date?->format('d/m/Y');

            return "Diária {$date} - {$payable->player->name}";
        }

        return 'Pagamento';
    }
}
