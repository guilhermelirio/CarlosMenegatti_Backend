<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Support;

use Piggly\Pix\Enums\QrCode as QrCodeEnum;
use Piggly\Pix\Parser;
use Piggly\Pix\StaticPayload;

final class PigglyPixCode
{
    /**
     * @return array{txid: string, payload: string, qr_code_image: string}
     */
    public static function generate(
        string $keyType,
        string $key,
        string $receiverName,
        string $city,
        int $amountCents,
        string $description,
        string $referenceId,
    ): array {
        self::loadLibrary();
        $txid = self::normalizeTxid($referenceId);

        $pix = new StaticPayload;
        $pix->setPixKey(self::parserKeyType($keyType), trim($key));
        $pix->setMerchantName($receiverName);
        $pix->setMerchantCity($city);
        $pix->setAmount($amountCents / 100);
        $pix->setDescription(mb_substr($description, 0, 40));
        $pix->setTid($txid);

        return [
            'txid' => $txid,
            'payload' => $pix->getPixCode(),
            'qr_code_image' => $pix->getQRCode(QrCodeEnum::OUTPUT_PNG),
        ];
    }

    public static function validateKey(string $keyType, string $key): void
    {
        self::loadLibrary();
        Parser::validate(self::parserKeyType($keyType), trim($key));
    }

    /**
     * Version 3.0 declares one implicitly nullable parameter, deprecated by PHP 8.4.
     * Keep the suppression scoped only to vendor autoload until upstream releases a fix.
     */
    private static function loadLibrary(): void
    {
        if (class_exists(Parser::class, false)) {
            return;
        }

        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_DEPRECATED);

        try {
            class_exists(Parser::class);
            class_exists(StaticPayload::class);
        } finally {
            error_reporting($errorReporting);
        }
    }

    private static function parserKeyType(string $keyType): string
    {
        return match (mb_strtolower(trim($keyType))) {
            'cpf', 'cnpj', 'document', 'documento' => Parser::KEY_TYPE_DOCUMENT,
            'email', 'e-mail' => Parser::KEY_TYPE_EMAIL,
            'telefone', 'phone' => Parser::KEY_TYPE_PHONE,
            'aleatoria', 'aleatória', 'random' => Parser::KEY_TYPE_RANDOM,
            default => $keyType,
        };
    }

    private static function normalizeTxid(string $referenceId): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', $referenceId) ?? '';

        return $normalized === '' ? '***' : mb_strtoupper(mb_substr($normalized, 0, 25));
    }
}
