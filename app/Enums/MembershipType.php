<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MembershipType: string implements HasColor, HasLabel
{
    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }

    case Monthly = 'monthly';
    case Daily = 'daily';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensalista',
            self::Daily => 'Diarista',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Monthly => 'info',
            self::Daily => 'gray',
        };
    }
}
