<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\OrganizationRole;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\DailyFee;
use App\Models\GameSession;
use App\Models\MonthlyFee;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Services\Billing\PaymentService;
use App\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setCurrentOrganization();
        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, '5000');
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, '2000');
        Setting::set(Setting::PIX_KEY_TYPE, 'email');
        Setting::set(Setting::PIX_KEY, 'grupo@exemplo.com');
        Setting::set(Setting::PIX_RECEIVER_NAME, 'CARLOS MENEGATTI FC');
        Setting::set(Setting::PIX_CITY, 'SAO PAULO');
    }

    private function playerUser(): User
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        Player::factory()->monthly()->create(['user_id' => $user->id]);
        $this->organization->users()->attach($user->id, ['role' => OrganizationRole::Member->value]);

        return $user;
    }

    public function test_login_returns_token(): void
    {
        $user = $this->playerUser();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'token_type', 'player' => ['id', 'name']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->playerUser();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_returns_profile(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('id', $user->player->id);
    }

    public function test_player_can_list_and_filter_unified_charges(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);
        $monthlyFee = MonthlyFee::factory()->for($user->player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);
        $game = GameSession::factory()->create(['scheduled_date' => '2026-08-11']);
        $dailyFee = DailyFee::factory()->for($user->player)->for($game)->create([
            'amount_cents' => 2000,
            'status' => FeeStatus::Overdue,
        ]);

        $this->getJson('/api/v1/me/charges')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'id' => $monthlyFee->id,
                'type' => 'monthly',
                'type_label' => 'Mensalidade',
                'can_pay' => true,
            ])
            ->assertJsonFragment([
                'id' => $dailyFee->id,
                'type' => 'daily',
                'type_label' => 'Diária',
                'game_id' => $game->id,
            ]);

        $this->getJson('/api/v1/me/charges?type=daily&status=overdue')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $dailyFee->id);
    }

    public function test_player_can_open_and_pay_a_unified_charge_with_pix(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);
        $fee = MonthlyFee::factory()->for($user->player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);

        $this->getJson("/api/v1/charges/{$fee->id}")
            ->assertOk()
            ->assertJsonPath('id', $fee->id)
            ->assertJsonPath('type', 'monthly');

        $this->postJson("/api/v1/charges/{$fee->id}/pix")
            ->assertCreated()
            ->assertJsonPath('method', 'pix')
            ->assertJsonStructure(['pix' => ['txid', 'qrcode']]);
    }

    public function test_invalid_pix_configuration_returns_a_stable_api_error(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);
        Setting::set(Setting::PIX_KEY_TYPE, 'email');
        Setting::set(Setting::PIX_KEY, 'chave-invalida');
        $fee = MonthlyFee::factory()->for($user->player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);

        $this->postJson("/api/v1/charges/{$fee->id}/pix")
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'PIX_CONFIGURATION_INVALID');

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_game_exposes_and_enforces_the_configurable_player_limit(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);
        $game = GameSession::factory()->create([
            'scheduled_date' => now()->addDay()->toDateString(),
            'max_players' => 1,
        ]);
        $confirmedPlayer = Player::factory()->monthly()->create();
        app(AttendanceService::class)->register($game, $confirmedPlayer, confirmed: true, attended: false);

        $this->getJson('/api/v1/games')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $game->id,
                'max_players' => 1,
                'confirmed_count' => 1,
                'available_spots' => 0,
                'is_full' => true,
            ]);

        $this->postJson("/api/v1/games/{$game->id}/confirm", ['confirmed' => true])
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_FAILED')
            ->assertJsonPath('errors.confirmed.0', 'O limite de jogadores deste jogo já foi atingido.');
    }

    public function test_pix_initiation_returns_qrcode(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);

        $fee = MonthlyFee::factory()->for($user->player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);

        $this->postJson("/api/v1/monthly-fees/{$fee->id}/pix")
            ->assertCreated()
            ->assertJsonPath('method', 'pix')
            ->assertJsonPath('status', 'pending')
            ->assertJsonStructure(['pix' => ['txid', 'qrcode']]);

        $this->assertSame(1, Payment::count());
    }

    public function test_cannot_pay_another_players_fee(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);

        $otherFee = MonthlyFee::factory()->create(['status' => FeeStatus::Pending]);

        $this->postJson("/api/v1/monthly-fees/{$otherFee->id}/pix")->assertForbidden();
        $this->getJson("/api/v1/charges/{$otherFee->id}")->assertNotFound();
        $this->postJson("/api/v1/charges/{$otherFee->id}/pix")->assertNotFound();
    }

    public function test_webhook_confirms_payment(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);

        $fee = MonthlyFee::factory()->for($user->player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);

        $payment = $this->postJson("/api/v1/monthly-fees/{$fee->id}/pix")->json();
        $txid = $payment['pix']['txid'];

        $this->postJson('/api/webhooks/pix/fake/'.config('pix.fake.webhook_secret'), [
            'txid' => $txid,
            'status' => 'PAID',
            'amount_cents' => 5000,
            'event_id' => 'evt_'.$txid,
        ])->assertOk();

        $this->assertSame(PaymentStatus::Confirmed, Payment::sole()->status);
        $this->assertSame(FeeStatus::Paid, $fee->fresh()->status);
    }

    public function test_webhook_rejects_bad_secret(): void
    {
        $this->postJson('/api/webhooks/pix/fake/wrong-secret', [
            'txid' => 'x', 'status' => 'PAID',
        ])->assertStatus(401);
    }

    public function test_user_with_multiple_organizations_must_select_one(): void
    {
        $user = $this->playerUser();
        $other = Organization::factory()->create();
        $other->users()->attach($user->id, ['role' => OrganizationRole::Member->value]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'ORGANIZATION_REQUIRED');

        $this->withHeader('X-Organization-Id', $this->organization->id)
            ->getJson('/api/v1/me')
            ->assertOk();
    }

    public function test_route_binding_cannot_resolve_another_organizations_fee(): void
    {
        $user = $this->playerUser();
        Sanctum::actingAs($user);

        $other = Organization::factory()->create();
        app(CurrentOrganization::class)->set($other);
        $otherFee = MonthlyFee::factory()->create(['status' => FeeStatus::Pending]);
        app(CurrentOrganization::class)->set($this->organization);

        $this->withHeader('X-Organization-Id', $this->organization->id)
            ->postJson("/api/v1/monthly-fees/{$otherFee->id}/pix")
            ->assertNotFound();
    }

    public function test_player_can_optionally_upload_and_download_a_payment_receipt(): void
    {
        Storage::fake('local');
        $user = $this->playerUser();
        Sanctum::actingAs($user);
        $fee = MonthlyFee::factory()->for($user->player)->create(['status' => FeeStatus::Pending]);
        $payment = app(PaymentService::class)
            ->registerManualPayment($fee, PaymentMethod::Pix);

        $this->post("/api/v1/payments/{$payment->id}/receipt", [
            'receipt' => UploadedFile::fake()->image('comprovante.jpg'),
        ])->assertOk()->assertJsonPath('has_receipt', true);

        $payment->refresh();
        $this->assertNotNull($payment->receipt_path);
        Storage::disk('local')->assertExists($payment->receipt_path);
        $this->get("/api/v1/payments/{$payment->id}/receipt")->assertOk();
    }
}
