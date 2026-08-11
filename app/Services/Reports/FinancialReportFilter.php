<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\MembershipType;
use App\Enums\TransactionType;

final readonly class FinancialReportFilter
{
    public function __construct(
        public ?string $playerId = null,
        public ?MembershipType $membershipType = null,
        public ?string $categoryId = null,
        public ?TransactionType $transactionType = null,
    ) {}

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $playerId = filled($values['player_id'] ?? null) ? (string) $values['player_id'] : null;
        $categoryId = filled($values['category_id'] ?? null) ? (string) $values['category_id'] : null;
        $membershipType = filled($values['membership_type'] ?? null)
            ? MembershipType::tryFrom((string) $values['membership_type'])
            : null;
        $transactionType = filled($values['transaction_type'] ?? null)
            ? TransactionType::tryFrom((string) $values['transaction_type'])
            : null;

        return new self($playerId, $membershipType, $categoryId, $transactionType);
    }

    /** @return array<string, string> */
    public function toQuery(): array
    {
        return array_filter([
            'player_id' => $this->playerId,
            'membership_type' => $this->membershipType?->value,
            'category_id' => $this->categoryId,
            'transaction_type' => $this->transactionType?->value,
        ], static fn (?string $value): bool => $value !== null);
    }
}
