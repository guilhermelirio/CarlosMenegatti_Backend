<?php

declare(strict_types=1);

namespace App\Services\CashFlow;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CashFlowService
{
    /** Record an income transaction originated from a confirmed payment. */
    public function recordFromPayment(Payment $payment, string $categoryName, string $description): Transaction
    {
        $category = $this->resolveCategory($categoryName, TransactionType::Income);

        return Transaction::query()->create([
            'type' => TransactionType::Income,
            'category_id' => $category->id,
            'player_id' => $payment->player_id,
            'payment_id' => $payment->id,
            'amount_cents' => $payment->amount_cents,
            'occurred_on' => ($payment->paid_at ?? CarbonImmutable::now())->toDateString(),
            'description' => $description,
        ]);
    }

    /** Manual cash-flow entry (income or expense). */
    public function record(
        TransactionType $type,
        int $amountCents,
        Carbon|CarbonImmutable $occurredOn,
        ?string $categoryId = null,
        ?string $description = null,
        ?string $playerId = null,
    ): Transaction {
        return Transaction::query()->create([
            'type' => $type,
            'category_id' => $categoryId,
            'player_id' => $playerId,
            'amount_cents' => $amountCents,
            'occurred_on' => $occurredOn->toDateString(),
            'description' => $description,
        ]);
    }

    /** Current cash balance in cents (all income minus all expenses). */
    public function balanceCents(): int
    {
        $income = (int) Transaction::query()->where('type', TransactionType::Income)->sum('amount_cents');
        $expense = (int) Transaction::query()->where('type', TransactionType::Expense)->sum('amount_cents');

        return $income - $expense;
    }

    public function reverse(Transaction $transaction, string $reason): Transaction
    {
        if ($transaction->reversals()->exists()) {
            throw ValidationException::withMessages(['transaction' => ['Este lançamento já possui estorno.']]);
        }

        return DB::transaction(fn (): Transaction => Transaction::query()->create([
            'type' => $transaction->type === TransactionType::Income
                ? TransactionType::Expense
                : TransactionType::Income,
            'category_id' => $transaction->category_id,
            'player_id' => $transaction->player_id,
            'reversal_of_id' => $transaction->id,
            'amount_cents' => $transaction->amount_cents,
            'occurred_on' => CarbonImmutable::now()->toDateString(),
            'description' => "Estorno: {$reason} — {$transaction->description}",
        ]));
    }

    private function resolveCategory(string $name, TransactionType $type): Category
    {
        return Category::query()->firstOrCreate(
            ['name' => $name, 'type' => $type],
            ['is_system' => true],
        );
    }
}
