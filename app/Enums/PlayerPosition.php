<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlayerPosition: string implements HasLabel
{
    public function getLabel(): string
    {
        return $this->label();
    }

    case Goalkeeper = 'goalkeeper';
    case Defender = 'defender';
    case Midfielder = 'midfielder';
    case Forward = 'forward';

    public function label(): string
    {
        return match ($this) {
            self::Goalkeeper => 'Goleiro',
            self::Defender => 'Zagueiro',
            self::Midfielder => 'Meia',
            self::Forward => 'Atacante',
        };
    }
}
