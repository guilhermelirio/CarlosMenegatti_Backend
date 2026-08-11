<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Renders any string (e.g. a Pix "copia e cola" payload) into a real, scannable
 * QR code as a base64 PNG data URI — generated 100% locally, no external service.
 * Shared by every Pix gateway (fake + manual/static).
 */
final class QrCodeGenerator
{
    public static function dataUri(string $payload): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => true,
            'scale' => 6,
            'eccLevel' => EccLevel::M,
        ]);

        return (new QRCode($options))->render($payload);
    }
}
