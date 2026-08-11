<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrganizationRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Treasurer = 'treasurer';
    case Viewer = 'viewer';
    case Member = 'member';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Treasurer => 'Tesoureiro',
            self::Viewer => 'Somente consulta',
            self::Member => 'Membro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Treasurer => 'success',
            self::Viewer => 'info',
            self::Member => 'gray',
        };
    }

    public function canAccessPanel(): bool
    {
        return $this !== self::Member;
    }

    public function canManageFinance(): bool
    {
        return $this === self::Admin || $this === self::Treasurer;
    }
}
