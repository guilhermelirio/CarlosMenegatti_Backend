<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Manual;

use App\Integrations\Pix\Contracts\PixGatewayContract;
use App\Integrations\Pix\Dto\CanonicalPixStatus;
use App\Integrations\Pix\Dto\PixCharge;
use App\Integrations\Pix\Dto\PixChargeRequest;
use App\Integrations\Pix\Dto\PixWebhookEvent;
use App\Integrations\Pix\Exceptions\PixConfigurationException;
use App\Integrations\Pix\Exceptions\WebhookSignatureException;
use App\Integrations\Pix\Support\PigglyPixCode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Throwable;

/**
 * Pix MANUAL — sem gateway/provedor.
 *
 * Gera um "BR Code" estático (copia-e-cola + QR) localmente, usando a chave Pix
 * fixa da organização configurada no painel (Configuração de valores). O atleta paga
 * no app do banco e o TESOUREIRO confirma o pagamento manualmente no admin —
 * não há webhook nem confirmação automática.
 */
final class StaticPixGateway implements PixGatewayContract
{
    public function slug(): string
    {
        return 'static';
    }

    public function createCharge(PixChargeRequest $request): PixCharge
    {
        $key = $this->setting(Setting::PIX_KEY, (string) config('pix.static.key', ''));
        $keyType = $this->setting(Setting::PIX_KEY_TYPE, (string) config('pix.static.key_type', 'email'));
        $receiver = $this->setting(Setting::PIX_RECEIVER_NAME, (string) config('pix.static.receiver_name', 'PELADA'));
        $city = $this->setting(Setting::PIX_CITY, (string) config('pix.static.city', 'SAO PAULO'));

        try {
            $pix = PigglyPixCode::generate(
                keyType: $keyType,
                key: $key,
                receiverName: $receiver,
                city: $city,
                amountCents: $request->amountCents,
                description: $request->description,
                referenceId: $request->referenceId,
            );
        } catch (Throwable) {
            throw new PixConfigurationException(
                'A configuração Pix da organização é inválida. Solicite a revisão da chave no painel administrativo.'
            );
        }

        return new PixCharge(
            txid: $pix['txid'],
            qrCodePayload: $pix['payload'],
            qrCodeImage: $pix['qr_code_image'],
            amountCents: $request->amountCents,
            status: CanonicalPixStatus::Pending,
            provider: $this->slug(),
            expiresAt: null, // BR Code estático não expira.
        );
    }

    public function getStatus(string $txid): CanonicalPixStatus
    {
        // Confirmação é manual (tesoureiro no painel). Sem estado remoto para consultar.
        return CanonicalPixStatus::Pending;
    }

    public function parseWebhook(Request $request): PixWebhookEvent
    {
        // Modo manual não recebe webhook de banco.
        throw new WebhookSignatureException('Pix manual não recebe confirmação por webhook.');
    }

    private function setting(string $key, string $fallback): string
    {
        $value = trim((string) Setting::get($key, $fallback));

        return $value !== '' ? $value : $fallback;
    }
}
