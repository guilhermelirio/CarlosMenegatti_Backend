<?php

declare(strict_types=1);

namespace App\Data\V1\Session;

use Spatie\LaravelData\Data;

class ConfirmAttendanceData extends Data
{
    public function __construct(
        public bool $confirmed = true,
    ) {}
}
