<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Tenancy\CurrentOrganization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class OrganizationMembershipService
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function createUser(
        Organization $organization,
        string $name,
        string $email,
        string $password,
        OrganizationRole $role,
    ): OrganizationMembership {
        $email = Str::lower(trim($email));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'data.email' => ['Este e-mail já possui uma conta. Use a ação “Vincular usuário existente”.'],
            ]);
        }

        return $this->within($organization, function () use ($name, $email, $password, $role): OrganizationMembership {
            return DB::transaction(function () use ($name, $email, $password, $role): OrganizationMembership {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                ]);

                return OrganizationMembership::query()->create([
                    'user_id' => $user->id,
                    'role' => $role,
                ]);
            });
        });
    }

    public function attachExisting(
        Organization $organization,
        string $email,
        OrganizationRole $role,
    ): OrganizationMembership {
        $email = Str::lower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['Nenhum usuário foi encontrado com este e-mail.'],
            ]);
        }

        return $this->within($organization, function () use ($user, $role): OrganizationMembership {
            if (OrganizationMembership::query()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['Este usuário já pertence à organização.'],
                ]);
            }

            return OrganizationMembership::query()->create([
                'user_id' => $user->id,
                'role' => $role,
            ]);
        });
    }

    public function updateRole(OrganizationMembership $membership, OrganizationRole $role): OrganizationMembership
    {
        return $this->within($membership->organization, function () use ($membership, $role): OrganizationMembership {
            if ($membership->role === OrganizationRole::Admin && $role !== OrganizationRole::Admin) {
                $this->ensureAnotherAdministratorExists($membership);
            }

            $membership->update(['role' => $role]);

            return $membership;
        });
    }

    public function remove(OrganizationMembership $membership): void
    {
        $this->within($membership->organization, function () use ($membership): void {
            if ($membership->role === OrganizationRole::Admin) {
                $this->ensureAnotherAdministratorExists($membership);
            }

            $membership->delete();
        });
    }

    private function ensureAnotherAdministratorExists(OrganizationMembership $membership): void
    {
        $hasAnotherAdministrator = OrganizationMembership::query()
            ->whereKeyNot($membership->getKey())
            ->where('role', OrganizationRole::Admin)
            ->exists();

        if (! $hasAnotherAdministrator) {
            throw ValidationException::withMessages([
                'data.role' => ['A organização precisa manter pelo menos um administrador.'],
            ]);
        }
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function within(Organization $organization, callable $callback): mixed
    {
        $previous = $this->currentOrganization->get();
        $this->currentOrganization->set($organization);

        try {
            return $callback();
        } finally {
            $this->currentOrganization->set($previous);
        }
    }
}
