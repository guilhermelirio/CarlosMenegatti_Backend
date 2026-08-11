<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesOrganizationOperations
{
    public static function canViewAny(): bool
    {
        return static::currentRole()?->canAccessPanel() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canWrite();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWrite();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canWrite();
    }

    public static function canDeleteAny(): bool
    {
        return static::canWrite();
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return static::canWrite();
    }

    public static function canRestoreAny(): bool
    {
        return static::canWrite();
    }

    protected static function canWrite(): bool
    {
        $role = static::currentRole();

        return $role === OrganizationRole::Admin
            || (static::treasurerCanWrite() && $role === OrganizationRole::Treasurer);
    }

    protected static function treasurerCanWrite(): bool
    {
        return false;
    }

    protected static function currentRole(): ?OrganizationRole
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        if (! $user instanceof User || ! $tenant instanceof Organization) {
            return null;
        }

        return $user->roleForOrganization($tenant);
    }
}
