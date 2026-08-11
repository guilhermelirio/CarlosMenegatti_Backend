<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Dto;

/**
 * Provider-agnostic request to create a Pix charge.
 */
final readonly class PixChargeRequest
{
    public function __construct(
        public int $amountCents,
        public string $payerName,
        public string $description,
        public string $referenceId,   // our internal payment id
        public int $expiresInSeconds = 3600,
        public ?string $payerDocument = null, // CPF/CNPJ, optional
    ) {}
}
