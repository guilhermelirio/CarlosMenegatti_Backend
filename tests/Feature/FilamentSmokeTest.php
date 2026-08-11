<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['is_staff' => true]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, '5000');
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, '2000');
        Setting::set(Setting::MONTHLY_FEE_DUE_DAY, '10');
    }

    public function test_admin_pages_render(): void
    {
        $urls = [
            '/admin',
            '/admin/players',
            '/admin/game-sessions',
            '/admin/charges',
            '/admin/monthly-fees',
            '/admin/daily-fees',
            '/admin/payments',
            '/admin/transactions',
            '/admin/categories',
            '/admin/manage-values',
            '/admin/financial-reports',
        ];

        $staff = $this->staff();

        foreach ($urls as $url) {
            $this->actingAs($staff)
                ->get($url)
                ->assertSuccessful();
        }
    }

    public function test_non_staff_cannot_access_admin(): void
    {
        $user = User::factory()->create(['is_staff' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
