<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /** Format an amount in cents as a BRL string, e.g. 12990 => "R$ 129,90". */
    public static function formatBRL(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }
}
