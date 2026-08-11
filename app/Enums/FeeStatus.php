<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeeStatus: string implements HasColor, HasLabel
{
    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }

    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Exempt = 'exempt';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Pago',
            self::Overdue => 'Vencido',
            self::Exempt => 'Isento',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Exempt => 'gray',
        };
    }

    public function isSettled(): bool
    {
        return $this === self::Paid || $this === self::Exempt;
    }
}
