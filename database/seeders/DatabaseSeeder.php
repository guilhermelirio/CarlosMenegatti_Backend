<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PlayerStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\GameSession;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Services\Billing\FeeGenerationService;
use App\Services\Billing\PaymentService;
use App\Services\CashFlow\CashFlowService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedAdmin();
        $categories = $this->seedCategories();
        [$monthlyPlayers, $dailyPlayers] = $this->seedPlayers();
        $this->seedFeesAndPayments($monthlyPlayers);
        $this->seedSessionsAndAttendance($monthlyPlayers, $dailyPlayers);
        $this->seedManualExpenses($categories);
    }

    private function seedSettings(): void
    {
        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, '5000'); // R$ 50,00
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, '2000');   // R$ 20,00
        Setting::set(Setting::MONTHLY_FEE_DUE_DAY, '10');

        // Pix manual — chave/recebedor placeholder (o tesoureiro ajusta no painel).
        Setting::set(Setting::PIX_KEY, 'pelada@exemplo.com');
        Setting::set(Setting::PIX_KEY_TYPE, 'email');
        Setting::set(Setting::PIX_RECEIVER_NAME, 'PELADA C MENEGATTI');
        Setting::set(Setting::PIX_CITY, 'SAO PAULO');
    }

    private function seedAdmin(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@pelada.test'],
            [
                'name' => 'Carlos Menegatti (Tesoureiro)',
                'password' => Hash::make('password'),
                'is_staff' => true,
            ],
        );
    }

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $income = ['Mensalidade', 'Diária', 'Patrocínio'];
        $expense = ['Aluguel do campo', 'Material esportivo', 'Arbitragem', 'Água', 'Premiação'];

        $map = [];

        foreach ($income as $name) {
            $map[$name] = Category::query()->firstOrCreate(
                ['name' => $name, 'type' => TransactionType::Income],
                ['is_system' => true],
            );
        }

        foreach ($expense as $name) {
            $map[$name] = Category::query()->firstOrCreate(
                ['name' => $name, 'type' => TransactionType::Expense],
                ['is_system' => true],
            );
        }

        return $map;
    }

    /** @return array{0: Collection<int, Player>, 1: Collection<int, Player>} */
    private function seedPlayers(): array
    {
        // Monthly members (mensalistas) — one with an app login.
        $appUser = User::query()->create([
            'name' => 'Atleta App',
            'email' => 'atleta@pelada.test',
            'password' => Hash::make('password'),
            'is_staff' => false,
        ]);

        $monthly = collect();
        $monthly->push(Player::factory()->monthly()->create([
            'name' => 'Ronaldo Nazário',
            'nickname' => 'Fenômeno',
            'user_id' => $appUser->id,
            'email' => 'atleta@pelada.test',
        ]));
        $monthly = $monthly->concat(Player::factory()->count(7)->monthly()->create());

        // One monthly member with an individual (override) fee.
        $monthly->push(Player::factory()->monthly()->create([
            'name' => 'Sócio Fundador',
            'monthly_fee_cents' => 3000,
        ]));

        // Diaristas.
        $daily = Player::factory()->count(6)->daily()->create();

        // Edge cases: inactive + guest.
        Player::factory()->monthly()->inactive()->create(['name' => 'Jogador Afastado']);
        Player::factory()->daily()->create(['name' => 'Convidado Ocasional', 'status' => PlayerStatus::Guest]);

        return [$monthly, $daily];
    }

    /** @param Collection<int, Player> $monthlyPlayers */
    private function seedFeesAndPayments($monthlyPlayers): void
    {
        $fees = app(FeeGenerationService::class);
        $payments = app(PaymentService::class);

        $now = CarbonImmutable::now();
        $current = $now;
        $previous = $now->subMonthNoOverflow();

        $fees->generateForMonth($previous->year, $previous->month);
        $fees->generateForMonth($current->year, $current->month);
        $fees->markOverdue();

        // Pay previous-month fees for most players (leaves a few as delinquent).
        $monthlyPlayers->take(6)->each(function (Player $player) use ($previous, $payments): void {
            $fee = $player->monthlyFees()
                ->where('reference_year', $previous->year)
                ->where('reference_month', $previous->month)
                ->first();

            if ($fee !== null && ! $fee->status->isSettled()) {
                $payments->registerManualPayment($fee, PaymentMethod::Pix);
            }
        });

        // Pay a couple of current-month fees in cash too.
        $monthlyPlayers->take(2)->each(function (Player $player) use ($current, $payments): void {
            $fee = $player->monthlyFees()
                ->where('reference_year', $current->year)
                ->where('reference_month', $current->month)
                ->first();

            if ($fee !== null && ! $fee->status->isSettled()) {
                $payments->registerManualPayment($fee, PaymentMethod::Cash);
            }
        });
    }

    /**
     * @param  Collection<int, Player>  $monthlyPlayers
     * @param  Collection<int, Player>  $dailyPlayers
     */
    private function seedSessionsAndAttendance($monthlyPlayers, $dailyPlayers): void
    {
        $attendance = app(AttendanceService::class);
        $payments = app(PaymentService::class);
        $now = CarbonImmutable::now();

        // Past session — with attendance registered (diaristas generate daily fees).
        $past = GameSession::factory()->create([
            'scheduled_date' => $now->subDays(3)->toDateString(),
            'start_time' => '19:30',
            'location' => 'Society Bola na Rede',
        ]);

        foreach ($monthlyPlayers->take(5) as $player) {
            $attendance->register($past, $player, confirmed: true, attended: true);
        }

        foreach ($dailyPlayers->take(4) as $index => $player) {
            $att = $attendance->register($past, $player, confirmed: true, attended: true);

            // Pay one of the generated daily fees to show a settled daily.
            if ($index === 0 && $att->dailyFee !== null) {
                $payments->registerManualPayment($att->dailyFee, PaymentMethod::Pix);
            }
        }

        // Upcoming session — only confirmations, no attendance yet.
        $upcoming = GameSession::factory()->create([
            'scheduled_date' => $now->addDays(4)->toDateString(),
            'start_time' => '20:00',
            'location' => 'Quadra do Zé',
        ]);

        foreach ($monthlyPlayers->take(6) as $player) {
            $attendance->register($upcoming, $player, confirmed: true, attended: false);
        }
    }

    /** @param array<string, Category> $categories */
    private function seedManualExpenses(array $categories): void
    {
        $cashFlow = app(CashFlowService::class);
        $now = CarbonImmutable::now();

        $cashFlow->record(TransactionType::Expense, 30000, $now->subDays(5), $categories['Aluguel do campo']->id, 'Aluguel mensal do campo');
        $cashFlow->record(TransactionType::Expense, 12000, $now->subDays(4), $categories['Material esportivo']->id, 'Bolas e coletes');
        $cashFlow->record(TransactionType::Expense, 8000, $now->subDays(3), $categories['Arbitragem']->id, 'Árbitro da última rodada');
        $cashFlow->record(TransactionType::Income, 50000, $now->subDays(10), $categories['Patrocínio']->id, 'Patrocínio bar do João');
    }
}
