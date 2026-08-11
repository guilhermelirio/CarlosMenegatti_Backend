<?php

declare(strict_types=1);

namespace App\Integrations\Pix;

use App\Integrations\Pix\Contracts\PixGatewayContract;
use App\Integrations\Pix\Fake\FakePixGateway;
use App\Integrations\Pix\Manual\StaticPixGateway;
use InvalidArgumentException;

/**
 * Resolves the active Pix gateway. Default is "static" (Pix manual: BR Code
 * estático + confirmação manual). "fake" fica disponível para testes do fluxo
 * de webhook. Provedores reais plugam aqui no futuro (um driver por provedor).
 */
final class PixManager
{
    public function driver(?string $name = null): PixGatewayContract
    {
        $name ??= (string) config('pix.default', 'static');

        return match ($name) {
            'static' => new StaticPixGateway,
            'fake' => new FakePixGateway,
            // 'efi' => new \App\Integrations\Pix\Efi\EfiPixGateway(...),
            default => throw new InvalidArgumentException("Unsupported Pix provider [{$name}]."),
        };
    }
}
