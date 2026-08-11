<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Integrations\Pix\Dto\CanonicalPixStatus;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\Billing\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPixWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<string> */
    public array $tags = ['pix', 'webhook'];

    public function __construct(
        private readonly string $webhookEventId,
        private readonly string $txid,
        private readonly string $status,
    ) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60, 300];
    }

    public function handle(PaymentService $payments): void
    {
        $status = CanonicalPixStatus::tryFrom($this->status) ?? CanonicalPixStatus::Unknown;

        $payment = Payment::query()->where('pix_txid', $this->txid)->first();

        if ($payment !== null && $status === CanonicalPixStatus::Paid) {
            $payments->confirm($payment);
        } elseif ($payment !== null && in_array($status, [CanonicalPixStatus::Cancelled, CanonicalPixStatus::Expired], true)) {
            if ($payment->status === PaymentStatus::Pending) {
                $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
            }
        }

        WebhookEvent::query()->whereKey($this->webhookEventId)
            ->update(['processed_at' => CarbonImmutable::now()]);
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('single')->error('Pix webhook processing failed', [
            'txid' => $this->txid,
            'status' => $this->status,
            'exception' => $e->getMessage(),
        ]);
    }
}
