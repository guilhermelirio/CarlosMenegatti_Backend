<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Dto;

use Carbon\CarbonImmutable;

/**
 * Provider-agnostic representation of a created Pix charge.
 */
final readonly class PixCharge
{
    public function __construct(
        public string $txid,
        public string $qrCodePayload,       // "copia e cola" / EMV string
        public ?string $qrCodeImage,        // base64 data URI (optional)
        public int $amountCents,
        public CanonicalPixStatus $status,
        public string $provider,
        public ?CarbonImmutable $expiresAt = null,
    ) {}
}
