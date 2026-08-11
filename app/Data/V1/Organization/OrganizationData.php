<?php

declare(strict_types=1);

namespace App\Data\V1\Organization;

use App\Models\Organization;
use Spatie\LaravelData\Data;

class OrganizationData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
    ) {}

    public static function fromModel(Organization $organization): self
    {
        return new self($organization->id, $organization->name, $organization->slug);
    }
}
