<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $user = User::factory()->create(['is_staff' => true]);
        $this->organization->users()->attach($user->id, ['role' => OrganizationRole::Admin->value]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setCurrentOrganization();
        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, '5000');
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, '2000');
        Setting::set(Setting::MONTHLY_FEE_DUE_DAY, '10');
    }

    public function test_admin_pages_render(): void
    {
        $urls = [
            "/admin/{$this->organization->slug}",
            "/admin/{$this->organization->slug}/players",
            "/admin/{$this->organization->slug}/game-sessions",
            "/admin/{$this->organization->slug}/charges",
            "/admin/{$this->organization->slug}/monthly-fees",
            "/admin/{$this->organization->slug}/daily-fees",
            "/admin/{$this->organization->slug}/payments",
            "/admin/{$this->organization->slug}/transactions",
            "/admin/{$this->organization->slug}/categories",
            "/admin/{$this->organization->slug}/organization-memberships",
            "/admin/{$this->organization->slug}/profile",
            "/admin/{$this->organization->slug}/manage-values",
            "/admin/{$this->organization->slug}/financial-reports",
            "/admin/{$this->organization->slug}/monthly-closings",
            "/admin/{$this->organization->slug}/audit-logs",
        ];

        $staff = $this->staff();

        $this->assertFalse($staff->can('create', Organization::class));
        $this->actingAs($staff)->get('/admin/new')->assertNotFound();

        foreach ($urls as $url) {
            $this->actingAs($staff)
                ->get($url)
                ->assertSuccessful();
        }
    }

    public function test_athlete_can_use_app_but_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_staff' => false, 'password' => bcrypt('password')]);
        $this->organization->users()->attach($user->id, ['role' => OrganizationRole::Member->value]);
        Player::factory()->monthly()->create(['user_id' => $user->id]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'player']);

        $this->actingAs($user)
            ->get("/admin/{$this->organization->slug}")
            ->assertForbidden();
    }
}
