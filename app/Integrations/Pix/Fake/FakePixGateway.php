<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Fake;

use App\Integrations\Pix\Contracts\PixGatewayContract;
use App\Integrations\Pix\Dto\CanonicalPixStatus;
use App\Integrations\Pix\Dto\PixCharge;
use App\Integrations\Pix\Dto\PixChargeRequest;
use App\Integrations\Pix\Dto\PixWebhookEvent;
use App\Integrations\Pix\Exceptions\WebhookSignatureException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Fully functional in-memory Pix gateway for development and tests.
 * Generates a deterministic (fake but plausible) EMV "copia e cola" payload so the
 * whole payment flow works end-to-end without any real provider credentials.
 *
 * Confirmation happens via the webhook endpoint (POST /webhooks/pix/fake/{secret})
 * which the panel/tests can call to simulate the bank confirming the payment.
 */
final class FakePixGateway implements PixGatewayContract
{
    public function slug(): string
    {
        return 'fake';
    }

    public function createCharge(PixChargeRequest $request): PixCharge
    {
        $txid = Str::of(Str::ulid()->toBase32())->lower()->limit(35, '')->value();

        $payload = $this->buildEmvPayload($txid, $request->amountCents, $request->description);

        return new PixCharge(
            txid: $txid,
            qrCodePayload: $payload,
            qrCodeImage: $this->fakeQrCodeImage(),
            amountCents: $request->amountCents,
            status: CanonicalPixStatus::Pending,
            provider: $this->slug(),
            expiresAt: CarbonImmutable::now()->addSeconds($request->expiresInSeconds),
        );
    }

    public function getStatus(string $txid): CanonicalPixStatus
    {
        // The fake gateway has no remote state; status transitions are driven by the
        // simulated webhook. Callers should read the Payment model for the real status.
        return CanonicalPixStatus::Pending;
    }

    public function parseWebhook(Request $request): PixWebhookEvent
    {
        $secret = (string) $request->route('secret');

        if ($secret === '' || $secret !== (string) config('pix.fake.webhook_secret')) {
            throw new WebhookSignatureException('Invalid fake Pix webhook secret.');
        }

        $txid = (string) $request->input('txid');
        $statusInput = strtoupper((string) $request->input('status', 'PAID'));

        $status = CanonicalPixStatus::tryFrom($statusInput) ?? CanonicalPixStatus::Paid;

        return new PixWebhookEvent(
            eventId: (string) $request->input('event_id', 'fake_'.$txid.'_'.$status->value),
            txid: $txid,
            status: $status,
            amountCents: (int) $request->input('amount_cents', 0),
            provider: $this->slug(),
            raw: $request->all(),
        );
    }

    private function buildEmvPayload(string $txid, int $amountCents, string $description): string
    {
        // Simplified BR Code-like string. Not a real CRC-valid EMV, but stable and
        // recognizable for dev/testing. A real provider returns the canonical payload.
        $amount = number_format($amountCents / 100, 2, '.', '');
        $desc = Str::of($description)->ascii()->limit(25, '')->value();

        return "00020126FAKE-PIX-BR-CODE520400005303986540{$amount}5802BR59{$desc}6009SAO PAULO62".
            strtoupper($txid).'6304FAKE';
    }

    private function fakeQrCodeImage(): string
    {
        // 1x1 transparent PNG placeholder as a data URI.
        return 'data:image/png;base64,'.
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    }
}
