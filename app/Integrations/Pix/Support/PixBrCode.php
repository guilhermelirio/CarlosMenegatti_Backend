<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Support;

use Illuminate\Support\Str;

/**
 * Builds a static Pix "BR Code" (EMV/MPM payload) locally — no external service.
 *
 * Layout (EMV-MPM, per Banco Central "Manual de Padrões para Iniciação do Pix"):
 *   00 Payload Format Indicator           = "01"
 *   01 Point of Initiation Method         = "12" (single use, since we embed an amount)
 *   26 Merchant Account Information (Pix)
 *        00 GUI                           = "br.gov.bcb.pix"
 *        01 Chave                         = <pix key>
 *        02 Info adicional (descrição)    = <description, optional>
 *   52 Merchant Category Code             = "0000"
 *   53 Transaction Currency               = "986" (BRL)
 *   54 Transaction Amount                 = "50.00"
 *   58 Country Code                       = "BR"
 *   59 Merchant Name (recebedor)          = <=25 chars
 *   60 Merchant City                      = <=15 chars
 *   62 Additional Data Field
 *        05 Reference Label (txid)        = <=25 alnum, or "***"
 *   63 CRC16 (CCITT/XModem, poly 0x1021, init 0xFFFF)
 */
final class PixBrCode
{
    public static function build(
        string $key,
        string $receiverName,
        string $city,
        int $amountCents,
        ?string $description = null,
        ?string $txid = null,
    ): string {
        $name = self::sanitize($receiverName, 25);
        $city = self::sanitize($city, 15);
        $amount = number_format($amountCents / 100, 2, '.', '');

        // Merchant Account Information (26)
        $mai = self::tlv('00', 'br.gov.bcb.pix').self::tlv('01', $key);
        if ($description !== null && $description !== '') {
            $mai .= self::tlv('02', self::sanitize($description, 40));
        }

        // Additional Data Field (62) — reference label / txid
        $reference = self::normalizeTxid($txid);
        $additional = self::tlv('05', $reference);

        $payload =
            self::tlv('00', '01').
            self::tlv('01', '12').
            self::tlv('26', $mai).
            self::tlv('52', '0000').
            self::tlv('53', '986').
            self::tlv('54', $amount).
            self::tlv('58', 'BR').
            self::tlv('59', $name).
            self::tlv('60', $city).
            self::tlv('62', $additional);

        // CRC placeholder + computed value.
        $payload .= '6304';

        return $payload.self::crc16($payload);
    }

    /** EMV TLV field: id + 2-digit length + value. */
    private static function tlv(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    private static function sanitize(string $value, int $max): string
    {
        $ascii = Str::ascii($value);
        $ascii = (string) preg_replace('/[^A-Za-z0-9 ]/', '', $ascii);

        return strtoupper(trim(Str::limit($ascii, $max, '')));
    }

    private static function normalizeTxid(?string $txid): string
    {
        if ($txid === null || $txid === '') {
            return '***';
        }

        $clean = (string) preg_replace('/[^A-Za-z0-9]/', '', $txid);

        return $clean === '' ? '***' : strtoupper(substr($clean, 0, 25));
    }

    /** CRC16-CCITT (XModem): poly 0x1021, init 0xFFFF, no reflection, no xorout. */
    private static function crc16(string $payload): string
    {
        $crc = 0xFFFF;

        for ($i = 0, $len = strlen($payload); $i < $len; $i++) {
            $crc ^= ord($payload[$i]) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
