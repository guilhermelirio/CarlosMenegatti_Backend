<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Category;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Setting;
use App\Models\User;
use App\Services\Organizations\OrganizationMembershipService;
use App\Services\Organizations\OrganizationOnboardingService;
use App\Tenancy\CurrentOrganization;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrganizationAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setCurrentOrganization();
    }

    public function test_onboarding_creates_defaults_and_administrator_membership(): void
    {
        $administrator = User::factory()->create();

        $created = app(OrganizationOnboardingService::class)->create(
            'Novo Grupo',
            'novo-grupo',
            $administrator,
        );

        app(CurrentOrganization::class)->set($created);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $created->id,
            'user_id' => $administrator->id,
            'role' => OrganizationRole::Admin->value,
        ]);
        $this->assertSame('5000', Setting::get(Setting::DEFAULT_MONTHLY_FEE_CENTS));
        $this->assertSame(9, Category::query()->where('is_system', true)->count());
    }

    public function test_can_create_a_new_user_and_membership(): void
    {
        $membership = app(OrganizationMembershipService::class)->createUser(
            $this->organization,
            'Novo Usuário',
            'novo@example.com',
            'password123',
            OrganizationRole::Member,
        );

        $this->assertSame('novo@example.com', $membership->user->email);
        $this->assertSame(OrganizationRole::Member, $membership->role);
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $membership->user_id,
        ]);
    }

    public function test_last_administrator_cannot_be_demoted_or_removed(): void
    {
        $administrator = OrganizationMembership::factory()->administrator()->create();
        $service = app(OrganizationMembershipService::class);

        try {
            $service->updateRole($administrator, OrganizationRole::Member);
            $this->fail('O último administrador foi rebaixado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.role', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $service->remove($administrator);
    }

    public function test_administrator_can_be_removed_when_another_one_exists(): void
    {
        $first = OrganizationMembership::factory()->administrator()->create();
        OrganizationMembership::factory()->administrator()->create();

        app(OrganizationMembershipService::class)->remove($first);

        $this->assertModelMissing($first);
        $this->assertSame(1, OrganizationMembership::query()->where('role', OrganizationRole::Admin)->count());
    }

    public function test_memberships_are_isolated_by_current_organization(): void
    {
        OrganizationMembership::factory()->create();
        $other = Organization::factory()->create();
        app(CurrentOrganization::class)->set($other);
        OrganizationMembership::factory()->create();

        $this->assertSame(1, OrganizationMembership::query()->count());
    }

    public function test_financial_report_requires_administrator_membership_for_the_tenant(): void
    {
        $administrator = User::factory()->create();
        OrganizationMembership::factory()->administrator()->create(['user_id' => $administrator->id]);

        $this->actingAs($administrator)
            ->get(route('reports.pdf', ['organization' => $this->organization]))
            ->assertOk();

        $other = Organization::factory()->create();

        $this->actingAs($administrator)
            ->get(route('reports.pdf', ['organization' => $other]))
            ->assertNotFound();
    }

    public function test_treasurer_and_viewer_can_access_panel_but_member_cannot(): void
    {
        foreach ([OrganizationRole::Treasurer, OrganizationRole::Viewer, OrganizationRole::Member] as $role) {
            $user = User::factory()->create();
            $this->organization->users()->attach($user->id, ['role' => $role->value]);

            $this->assertSame($role !== OrganizationRole::Member, $user->canAccessPanel(Filament::getPanel('admin')));
        }
    }

    public function test_financial_report_can_be_exported_as_csv_for_excel(): void
    {
        $administrator = User::factory()->create();
        OrganizationMembership::factory()->administrator()->create(['user_id' => $administrator->id]);

        $this->actingAs($administrator)
            ->get(route('reports.csv', ['organization' => $this->organization]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
