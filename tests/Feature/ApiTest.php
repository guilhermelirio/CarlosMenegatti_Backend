<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\OrganizationRole;
use App\Enums\PaymentStatus;
use App\Models\MonthlyFee;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use App\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
