<?php

declare(strict_types=1);

namespace App\Data\V1\Auth;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class LoginData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required, StringType]
        public string $password,
        #[StringType, Max(255)]
        public string $device_name = 'flutter-app',
    ) {}
}
