<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\OrganizationRole;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\AuditLog;
use App\Models\DailyFee;
use App\Models\GameSession;
use App\Models\MonthlyFee;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Services\Billing\FeeGenerationService;
use App\Services\Billing\MonthlyFeeAdjustmentService;
use App\Services\Billing\PaymentService;
use App\Services\Reports\MonthlyClosingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setCurrentOrganization();
        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, '10000');
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, '2500');
        Setting::set(Setting::MONTHLY_FEE_DUE_DAY, '10');
        Setting::set(Setting::LATE_FEE_PERCENT, '0');
        Setting::set(Setting::MONTHLY_INTEREST_PERCENT, '0');
    }

    public function test_permanent_and_charge_specific_discounts_are_combined(): void
    {
        $player = Player::factory()->monthly()->create([
            'monthly_discount_cents' => 1000,
            'monthly_discount_percent' => 10,
        ]);

        app(FeeGenerationService::class)->generateForMonth(2026, 8);
        $fee = $player->monthlyFees()->sole();

        $this->assertSame(10000, $fee->gross_amount_cents);
        $this->assertSame(2000, $fee->discount_cents);
        $this->assertSame(8000, $fee->amount_cents);

        app(MonthlyFeeAdjustmentService::class)->applyDiscount($fee, 500, 5);

        $this->assertSame(3000, $fee->refresh()->discount_cents);
        $this->assertSame(7000, $fee->amount_cents);
    }

    public function test_permanent_gratuity_and_single_month_exemption_keep_history(): void
    {
        $freePlayer = Player::factory()->monthly()->create(['is_permanently_exempt' => true]);
        $regularPlayer = Player::factory()->monthly()->create();

        app(FeeGenerationService::class)->generateForMonth(2026, 8);

        $freeFee = $freePlayer->monthlyFees()->sole();
        $this->assertSame(FeeStatus::Exempt, $freeFee->status);
        $this->assertSame(0, $freeFee->amount_cents);
        $this->assertSame(10000, $freeFee->gross_amount_cents);

        $regularFee = $regularPlayer->monthlyFees()->sole();
        app(MonthlyFeeAdjustmentService::class)->exempt($regularFee);
        $this->assertSame(FeeStatus::Exempt, $regularFee->refresh()->status);
        $this->assertSame(0, $regularFee->amount_cents);
    }

    public function test_configured_late_fee_and_monthly_interest_are_applied(): void
    {
        Setting::set(Setting::LATE_FEE_PERCENT, '2');
        Setting::set(Setting::MONTHLY_INTEREST_PERCENT, '1');
        $fee = MonthlyFee::factory()->create([
            'gross_amount_cents' => 10000,
            'amount_cents' => 10000,
            'discount_cents' => 0,
            'due_date' => '2026-06-10',
            'status' => FeeStatus::Pending,
        ]);

        $updated = app(FeeGenerationService::class)->markOverdue(CarbonImmutable::parse('2026-08-11'));

        $this->assertSame(1, $updated);
        $this->assertSame(FeeStatus::Overdue, $fee->refresh()->status);
        $this->assertSame(200, $fee->late_fee_cents);
        $this->assertSame(200, $fee->interest_cents);
        $this->assertSame(10400, $fee->amount_cents);
    }

    public function test_guest_has_no_app_login_and_generates_guest_revenue_per_game(): void
    {
        $user = User::factory()->create();
        $guest = Player::factory()->guest()->create(['user_id' => $user->id]);
        $session = GameSession::factory()->create(['daily_fee_cents' => 3000]);

        $this->assertNull($guest->user_id);

        app(AttendanceService::class)->register($session, $guest, confirmed: true, attended: true);
        $fee = $guest->dailyFees()->sole();
        $this->assertSame(3000, $fee->amount_cents);

        app(PaymentService::class)->registerManualPayment($fee, PaymentMethod::Cash);

        $transaction = Transaction::query()->sole();
        $this->assertSame(TransactionType::Income, $transaction->type);
        $this->assertSame('Convidado', $transaction->category?->name);
    }

    public function test_unpaid_fee_becomes_overdue_after_the_guest_game(): void
    {
        $session = GameSession::factory()->create(['scheduled_date' => '2026-08-01']);
        $fee = DailyFee::factory()->for($session, 'gameSession')->create([
            'status' => FeeStatus::Pending,
        ]);

        $updated = app(FeeGenerationService::class)->markOverdue(CarbonImmutable::parse('2026-08-11'));

        $this->assertSame(1, $updated);
        $this->assertSame(FeeStatus::Overdue, $fee->refresh()->status);
    }

    public function test_month_closing_blocks_changes_and_admin_can_reopen_it(): void
    {
        $administrator = User::factory()->create();
        $this->organization->users()->attach($administrator->id, ['role' => OrganizationRole::Admin->value]);
        $this->actingAs($administrator);
        $transaction = Transaction::factory()->income()->create([
            'category_id' => null,
            'amount_cents' => 5000,
            'occurred_on' => '2026-07-15',
        ]);
        MonthlyFee::factory()->overdue()->create([
            'reference_year' => 2026,
            'reference_month' => 7,
            'gross_amount_cents' => 2500,
            'amount_cents' => 2500,
            'due_date' => '2026-07-10',
        ]);

        $closing = app(MonthlyClosingService::class)->close(2026, 7, $administrator);
        $this->assertTrue($closing->is_closed);
        $this->assertSame(5000, data_get($closing->snapshot, 'cash.income_cents'));
        $this->assertSame(2500, data_get($closing->snapshot, 'total_owed_cents'));

        try {
            $transaction->update(['amount_cents' => 6000]);
            $this->fail('Um lançamento de mês fechado foi alterado.');
        } catch (ValidationException) {
            $this->assertSame(5000, $transaction->fresh()->amount_cents);
        }

        $closedFee = MonthlyFee::query()->where('reference_year', 2026)->where('reference_month', 7)->firstOrFail();
        try {
            app(MonthlyFeeAdjustmentService::class)->applyDiscount($closedFee, 100, 0);
            $this->fail('Uma mensalidade de mês fechado recebeu desconto.');
        } catch (ValidationException) {
            $this->assertSame(2500, $closedFee->fresh()->amount_cents);
        }

        $treasurer = User::factory()->create();
        $this->organization->users()->attach($treasurer->id, ['role' => OrganizationRole::Treasurer->value]);
        try {
            app(MonthlyClosingService::class)->reopen($closing, $treasurer, 'Tentativa sem permissão');
            $this->fail('Um tesoureiro reabriu o mês.');
        } catch (ValidationException) {
            $this->assertTrue($closing->fresh()->is_closed);
        }

        app(MonthlyClosingService::class)->reopen($closing, $administrator, 'Correção autorizada');
        $transaction->update(['amount_cents' => 6000]);

        $this->assertSame(6000, $transaction->fresh()->amount_cents);
        $this->assertDatabaseHas('audit_logs', ['event' => 'month_reopened']);
    }

    public function test_transaction_edit_delete_and_restore_are_audited_and_recoverable(): void
    {
        $transaction = Transaction::factory()->create(['category_id' => null]);
        $transaction->update(['description' => 'Descrição corrigida']);
        $transaction->delete();

        $this->assertSoftDeleted($transaction);
        $this->assertSame(1, AuditLog::query()->where('event', 'transaction_deleted')->count());

        $transaction->restore();
        $this->assertNotSoftDeleted($transaction);
        $this->assertSame(1, AuditLog::query()->where('event', 'transaction_restored')->count());
    }
}
