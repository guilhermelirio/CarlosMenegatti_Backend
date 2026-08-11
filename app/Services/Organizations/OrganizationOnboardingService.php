<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Setting;
use App\Models\User;
use App\Tenancy\CurrentOrganization;
use Illuminate\Support\Facades\DB;

final readonly class OrganizationOnboardingService
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function create(string $name, string $slug, User $administrator): Organization
    {
        $previous = $this->currentOrganization->get();

        try {
            return DB::transaction(function () use ($name, $slug, $administrator): Organization {
                $organization = Organization::query()->create(['name' => $name, 'slug' => $slug]);
                $this->currentOrganization->set($organization);

                OrganizationMembership::query()->create([
                    'user_id' => $administrator->id,
                    'role' => OrganizationRole::Admin,
                ]);

                $this->createDefaultSettings();
                $this->createDefaultCategories();

                return $organization;
            });
        } finally {
            $this->currentOrganization->set($previous);
        }
    }

    private function createDefaultSettings(): void
    {
        $settings = [
            Setting::DEFAULT_MONTHLY_FEE_CENTS => '5000',
            Setting::DEFAULT_DAILY_FEE_CENTS => '2000',
            Setting::MONTHLY_FEE_DUE_DAY => '10',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->create(['key' => $key, 'value' => $value]);
        }
    }

    private function createDefaultCategories(): void
    {
        $categories = [
            TransactionType::Income->value => ['Mensalidade', 'Diária', 'Patrocínio'],
            TransactionType::Expense->value => ['Aluguel do campo', 'Material esportivo', 'Arbitragem', 'Água', 'Premiação'],
        ];

        foreach ($categories as $type => $names) {
            foreach ($names as $name) {
                Category::query()->create(['name' => $name, 'type' => $type, 'is_system' => true]);
            }
        }
    }
}
