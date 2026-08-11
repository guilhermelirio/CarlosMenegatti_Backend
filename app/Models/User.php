<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property bool $is_staff
 */
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_staff',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
        ];
    }

    /** @return HasMany<Player, $this> */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /** Current tenant's player profile. @return HasOne<Player, $this> */
    public function player(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    /** @return BelongsToMany<Organization, $this, OrganizationMembership, 'pivot'> */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(OrganizationMembership::class)
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    /** @return HasMany<OrganizationMembership, $this> */
    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /** @return Collection<int, Organization> */
    public function getTenants(Panel $panel): Collection
    {
        return $this->organizations()
            ->wherePivotIn('role', array_map(
                fn (OrganizationRole $role): string => $role->value,
                array_filter(OrganizationRole::cases(), fn (OrganizationRole $role): bool => $role->canAccessPanel()),
            ))
            ->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof Organization
            && $this->organizations()
                ->whereKey($tenant->getKey())
                ->wherePivotIn('role', array_map(
                    fn (OrganizationRole $role): string => $role->value,
                    array_filter(OrganizationRole::cases(), fn (OrganizationRole $role): bool => $role->canAccessPanel()),
                ))
                ->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->organizations()
            ->wherePivotIn('role', array_map(
                fn (OrganizationRole $role): string => $role->value,
                array_filter(OrganizationRole::cases(), fn (OrganizationRole $role): bool => $role->canAccessPanel()),
            ))
            ->exists();
    }

    public function roleForOrganization(Organization|string $organization): ?OrganizationRole
    {
        $organizationId = $organization instanceof Organization ? $organization->getKey() : $organization;
        $membership = $this->organizations()->whereKey($organizationId)->first()?->pivot;

        return $membership instanceof OrganizationMembership ? $membership->role : null;
    }
}
