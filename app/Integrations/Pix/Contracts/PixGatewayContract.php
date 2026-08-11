<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Contracts;

use App\Integrations\Pix\Dto\CanonicalPixStatus;
use App\Integrations\Pix\Dto\PixCharge;
use App\Integrations\Pix\Dto\PixChargeRequest;
use App\Integrations\Pix\Dto\PixWebhookEvent;
use Illuminate\Http\Request;

/**
 * Abstraction implemented by every Pix provider (Efí, Mercado Pago, Asaas, Woovi, ...).
 * The rest of the system depends only on this contract, never on a concrete provider.
 */
interface PixGatewayContract
{
    /** Machine slug of the provider (e.g. "fake", "efi", "mercadopago"). */
    public function slug(): string;

    /** Create a Pix charge and return the QR code / copia-e-cola payload. */
    public function createCharge(PixChargeRequest $request): PixCharge;

    /** Query the current status of a charge by its txid. */
    public function getStatus(string $txid): CanonicalPixStatus;

    /**
     * Validate the signature of an inbound webhook and normalize it.
     * Throws WebhookSignatureException when the signature is invalid.
     */
    public function parseWebhook(Request $request): PixWebhookEvent;
}
