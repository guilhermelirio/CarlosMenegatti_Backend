<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Dto;

/**
 * Canonical status vocabulary every Pix provider maps into.
 */
enum CanonicalPixStatus: string
{
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Expired = 'EXPIRED';
    case Refunded = 'REFUNDED';
    case Cancelled = 'CANCELLED';
    case Unknown = 'UNKNOWN';
}
