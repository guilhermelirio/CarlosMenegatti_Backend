<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Organization;

final class CurrentOrganization
{
    private ?Organization $organization = null;

    public function set(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function get(): ?Organization
    {
        return $this->organization;
    }

    public function id(): ?string
    {
        return $this->organization?->getKey();
    }

    public function clear(): void
    {
        $this->organization = null;
    }
}
