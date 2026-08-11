<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipType;
use App\Enums\TransactionType;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Category;
use App\Models\Player;
use App\Models\Transaction;
use App\Services\Reports\FinancialReportFilter;
use App\Services\Reports\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setCurrentOrganization();
    }

    public function test_cash_flow_series_respects_the_exact_selected_period(): void
    {
        Transaction::factory()->create([
            'type' => TransactionType::Income,
            'category_id' => null,
            'amount_cents' => 1000,
            'occurred_on' => '2026-01-05',
        ]);
        Transaction::factory()->create([
            'type' => TransactionType::Income,
            'category_id' => null,
            'amount_cents' => 2000,
            'occurred_on' => '2026-01-20',
        ]);
        Transaction::factory()->create([
            'type' => TransactionType::Expense,
            'category_id' => null,
            'amount_cents' => 500,
            'occurred_on' => '2026-02-05',
        ]);
        Transaction::factory()->create([
            'type' => TransactionType::Income,
            'category_id' => null,
            'amount_cents' => 4000,
            'occurred_on' => '2026-02-15',
        ]);

        $series = app(ReportService::class)->cashFlowSeriesForPeriod(
            CarbonImmutable::parse('2026-01-10'),
            CarbonImmutable::parse('2026-02-10'),
        );

        $this->assertSame([
            [
                'label' => '01/2026',
                'income_cents' => 2000,
                'expense_cents' => 0,
                'balance_cents' => 2000,
            ],
            [
                'label' => '02/2026',
                'income_cents' => 0,
                'expense_cents' => 500,
                'balance_cents' => -500,
            ],
        ], $series);
    }

    public function test_cash_entries_cannot_be_deleted_from_the_admin_panel(): void
    {
        $transaction = Transaction::factory()->create(['category_id' => null]);

        $this->assertFalse(TransactionResource::canDelete($transaction));
        $this->assertFalse(TransactionResource::canDeleteAny());
    }

    public function test_financial_report_combines_player_membership_category_and_type_filters(): void
    {
        $monthly = Player::factory()->monthly()->create();
        $daily = Player::factory()->daily()->create();
        $income = Category::factory()->income()->create();
        $sponsorship = Category::factory()->income()->create();

        Transaction::factory()->income()->create([
            'player_id' => $monthly->id,
            'category_id' => $income->id,
            'amount_cents' => 1000,
            'occurred_on' => '2026-08-01',
        ]);
        Transaction::factory()->income()->create([
            'player_id' => $daily->id,
            'category_id' => $income->id,
            'amount_cents' => 2000,
            'occurred_on' => '2026-08-02',
        ]);
        Transaction::factory()->income()->create([
            'player_id' => $monthly->id,
            'category_id' => $sponsorship->id,
            'amount_cents' => 4000,
            'occurred_on' => '2026-08-03',
        ]);

        $reports = app(ReportService::class);
        $from = CarbonImmutable::parse('2026-08-01');
        $to = CarbonImmutable::parse('2026-08-31');

        $byPlayerAndCategory = $reports->cashFlowByPeriod($from, $to, new FinancialReportFilter(
            playerId: $monthly->id,
            categoryId: $income->id,
            transactionType: TransactionType::Income,
        ));
        $byMembership = $reports->cashFlowByPeriod($from, $to, new FinancialReportFilter(
            membershipType: MembershipType::Daily,
        ));

        $this->assertSame(1000, $byPlayerAndCategory['income_cents']);
        $this->assertSame(2000, $byMembership['income_cents']);
    }
}
