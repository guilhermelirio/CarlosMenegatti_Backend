<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\DailyFee;
use App\Models\GameSession;
use App\Models\MonthlyFee;
use App\Models\Payment;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\Attendance\AttendanceService;
use App\Services\Billing\FeeGenerationService;
use App\Services\Billing\PaymentService;
use App\Services\CashFlow\CashFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setCurrentOrganization();
        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, '5000');
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, '2000');
        Setting::set(Setting::MONTHLY_FEE_DUE_DAY, '10');
    }

    public function test_monthly_fee_generation_is_idempotent(): void
    {
        Player::factory()->count(3)->monthly()->create();
        Player::factory()->daily()->create(); // should be ignored

        $service = app(FeeGenerationService::class);

        $this->assertSame(3, $service->generateForMonth(2026, 7));
        $this->assertSame(0, $service->generateForMonth(2026, 7)); // no duplicates
        $this->assertSame(3, MonthlyFee::count());
    }

    public function test_individual_fee_overrides_default(): void
    {
        $player = Player::factory()->monthly()->create(['monthly_fee_cents' => 3000]);

        app(FeeGenerationService::class)->generateForMonth(2026, 7);

        $this->assertSame(3000, $player->monthlyFees()->sole()->amount_cents);
    }

    public function test_overdue_marking(): void
    {
        $player = Player::factory()->monthly()->create();
        MonthlyFee::factory()->for($player)->create([
            'status' => FeeStatus::Pending,
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $updated = app(FeeGenerationService::class)->markOverdue();

        $this->assertSame(1, $updated);
        $this->assertSame(FeeStatus::Overdue, $player->monthlyFees()->sole()->status);
    }

    public function test_diarista_attendance_generates_daily_fee(): void
    {
        $session = GameSession::factory()->create(['daily_fee_cents' => 2500]);
        $diarista = Player::factory()->daily()->create();
        $mensalista = Player::factory()->monthly()->create();

        $service = app(AttendanceService::class);
        $service->register($session, $diarista, confirmed: true, attended: true);
        $service->register($session, $mensalista, confirmed: true, attended: true);

        $this->assertSame(1, DailyFee::count());
        $fee = DailyFee::sole();
        $this->assertSame($diarista->id, $fee->player_id);
        $this->assertSame(2500, $fee->amount_cents);
    }

    public function test_unmarking_attendance_removes_pending_daily_fee(): void
    {
        $session = GameSession::factory()->create();
        $diarista = Player::factory()->daily()->create();

        $service = app(AttendanceService::class);
        $att = $service->register($session, $diarista, confirmed: true, attended: true);
        $this->assertSame(1, DailyFee::count());

        $att->update(['attended' => false]);
        $this->assertSame(0, DailyFee::count());
    }

    public function test_confirming_payment_settles_fee_and_posts_to_cash_flow(): void
    {
        $player = Player::factory()->monthly()->create();
        $fee = MonthlyFee::factory()->for($player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);

        $payment = app(PaymentService::class)->registerManualPayment($fee, PaymentMethod::Pix);

        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame(FeeStatus::Paid, $fee->fresh()->status);
        $this->assertSame(1, Transaction::count());
        $this->assertSame(5000, app(CashFlowService::class)->balanceCents());
    }

    public function test_pix_initiation_is_idempotent_per_payable(): void
    {
        $player = Player::factory()->monthly()->create();
        $fee = MonthlyFee::factory()->for($player)->create(['status' => FeeStatus::Pending]);

        $service = app(PaymentService::class);
        $first = $service->initiatePix($fee);
        $second = $service->initiatePix($fee);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Payment::count());
        $this->assertNotNull($first->pix_qrcode);
    }
}
