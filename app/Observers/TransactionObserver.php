<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;
use App\Services\Audit\AuditService;
use App\Services\Reports\MonthlyClosingService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final readonly class TransactionObserver
{
    public function __construct(
        private MonthlyClosingService $closings,
        private AuditService $audit,
    ) {}

    public function creating(Transaction $transaction): void
    {
        $this->ensureOpen((string) $transaction->occurred_on);
    }

    public function updating(Transaction $transaction): void
    {
        $this->ensureOpen((string) $transaction->getOriginal('occurred_on'));
        $this->ensureOpen((string) $transaction->occurred_on);
    }

    public function deleting(Transaction $transaction): void
    {
        if ($transaction->isForceDeleting()) {
            throw ValidationException::withMessages(['transaction' => ['Lançamentos financeiros não podem ser apagados definitivamente.']]);
        }

        $this->ensureOpen((string) $transaction->occurred_on);
    }

    public function restoring(Transaction $transaction): void
    {
        $this->ensureOpen((string) $transaction->occurred_on);
    }

    public function created(Transaction $transaction): void
    {
        $this->audit->record('transaction_created', $transaction, after: $transaction->attributesToArray());
    }

    public function updated(Transaction $transaction): void
    {
        $before = array_intersect_key($transaction->getOriginal(), $transaction->getChanges());
        $this->audit->record('transaction_updated', $transaction, $before, $transaction->getChanges());
    }

    public function deleted(Transaction $transaction): void
    {
        $this->audit->record('transaction_deleted', $transaction, $transaction->attributesToArray());
    }

    public function restored(Transaction $transaction): void
    {
        $this->audit->record('transaction_restored', $transaction, after: $transaction->attributesToArray());
    }

    private function ensureOpen(string $date): void
    {
        if ($this->closings->isClosed(CarbonImmutable::parse($date))) {
            throw ValidationException::withMessages([
                'occurred_on' => ['O mês está fechado. Um administrador precisa reabri-lo antes desta alteração.'],
            ]);
        }
    }
}
