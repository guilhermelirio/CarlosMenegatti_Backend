<?php

declare(strict_types=1);

namespace App\Integrations\Pix\Manual;

use App\Integrations\Pix\Contracts\PixGatewayContract;
use App\Integrations\Pix\Dto\CanonicalPixStatus;
use App\Integrations\Pix\Dto\PixCharge;
use App\Integrations\Pix\Dto\PixChargeRequest;
use App\Integrations\Pix\Dto\PixWebhookEvent;
use App\Integrations\Pix\Exceptions\WebhookSignatureException;
use App\Integrations\Pix\Support\PixBrCode;
use App\Integrations\Pix\Support\QrCodeGenerator;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Pix MANUAL — sem gateway/provedor.
 *
 * Gera um "BR Code" estático (copia-e-cola + QR) localmente, usando a chave Pix
 * fixa da pelada configurada no painel (Configuração de valores). O atleta paga
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
        $receiver = $this->setting(Setting::PIX_RECEIVER_NAME, (string) config('pix.static.receiver_name', 'PELADA'));
        $city = $this->setting(Setting::PIX_CITY, (string) config('pix.static.city', 'SAO PAULO'));

        // O txid referencia o nosso pagamento interno (rastreável, mas informativo).
        $txid = $request->referenceId;

        $payload = PixBrCode::build(
            key: $key,
            receiverName: $receiver,
            city: $city,
            amountCents: $request->amountCents,
            description: $request->description,
            txid: $txid,
        );

        return new PixCharge(
            txid: $txid,
            qrCodePayload: $payload,
            qrCodeImage: $key === '' ? null : QrCodeGenerator::dataUri($payload),
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
