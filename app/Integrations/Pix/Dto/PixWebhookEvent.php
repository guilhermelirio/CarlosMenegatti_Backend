<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Dto;

/**
 * Normalized inbound Pix webhook event, produced by a provider's WebhookParser.
 */
final readonly class PixWebhookEvent
{
    public function __construct(
        public string $eventId,        // provider event id for dedupe
        public string $txid,
        public CanonicalPixStatus $status,
        public int $amountCents,
        public string $provider,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
