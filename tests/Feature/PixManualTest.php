<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Integrations\Pix\Exceptions\PixConfigurationException;
use App\Integrations\Pix\PixManager;
use App\Integrations\Pix\Support\PigglyPixCode;
use App\Models\MonthlyFee;
use App\Models\Payment;
use App\Models\Player;
use App\Models\Setting;
use App\Services\Billing\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Piggly\Pix\Exceptions\InvalidPixKeyException;
use Tests\TestCase;

class PixManualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setCurrentOrganization();
        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, '5000');
        Setting::set(Setting::PIX_KEY_TYPE, 'email');
        Setting::set(Setting::PIX_KEY, 'pelada@exemplo.com');
        Setting::set(Setting::PIX_RECEIVER_NAME, 'PELADA C MENEGATTI');
        Setting::set(Setting::PIX_CITY, 'SAO PAULO');
    }

    public function test_default_gateway_is_static_manual(): void
    {
        $this->assertSame('static', app(PixManager::class)->driver()->slug());
    }

    public function test_br_code_has_valid_crc_and_contains_key(): void
    {
        $pix = PigglyPixCode::generate(
            keyType: 'email',
            key: 'pelada@exemplo.com',
            receiverName: 'PELADA C MENEGATTI',
            city: 'SAO PAULO',
            amountCents: 5000,
            description: 'Mensalidade 07/2026',
            referenceId: 'ABC123',
        );
        $payload = $pix['payload'];

        $this->assertStringStartsWith('000201', $payload);
        $this->assertStringContainsString('br.gov.bcb.pix', $payload);
        $this->assertStringContainsString('pelada@exemplo.com', $payload);
        $this->assertStringContainsString('5303986', $payload);
        $this->assertStringContainsString('540550.00', $payload);
        $this->assertStringStartsWith('data:image/png;base64,', $pix['qr_code_image']);
        $this->assertSame('ABC123', $pix['txid']);

        $body = substr($payload, 0, -4);
        $this->assertSame($this->crc16($body), substr($payload, -4));
    }

    public function test_pix_key_types_are_validated_by_the_library(): void
    {
        PigglyPixCode::validateKey('cpf', '529.982.247-25');
        PigglyPixCode::validateKey('telefone', '+5511999999999');
        PigglyPixCode::validateKey('aleatoria', 'aae2196f-5f93-46e4-89e6-73bf4138427b');

        $this->expectException(InvalidPixKeyException::class);
        PigglyPixCode::validateKey('email', 'chave-invalida');
    }

    public function test_initiate_pix_generates_static_charge_and_pending_payment(): void
    {
        $player = Player::factory()->monthly()->create();
        $fee = MonthlyFee::factory()->for($player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);

        $payment = app(PaymentService::class)->initiatePix($fee);

        $this->assertSame('static', $payment->pix_provider);
        $this->assertStringStartsWith('000201', (string) $payment->pix_qrcode);
        $this->assertStringContainsString('pelada@exemplo.com', (string) $payment->pix_qrcode);
        $this->assertStringStartsWith('data:image/png;base64,', (string) $payment->pix_qrcode_image);
        $this->assertSame(25, strlen((string) $payment->pix_txid));
        $this->assertNull($payment->pix_expires_at);
    }

    public function test_invalid_pix_configuration_does_not_create_a_payment(): void
    {
        Setting::set(Setting::PIX_KEY, 'chave-invalida');
        $player = Player::factory()->monthly()->create();
        $fee = MonthlyFee::factory()->for($player)->create([
            'amount_cents' => 5000,
            'status' => FeeStatus::Pending,
        ]);

        try {
            app(PaymentService::class)->initiatePix($fee);
            $this->fail('Uma configuração Pix inválida foi aceita.');
        } catch (PixConfigurationException $exception) {
            $this->assertStringContainsString('configuração Pix', $exception->getMessage());
        }

        $this->assertSame(0, Payment::query()->count());
    }

    private function crc16(string $payload): string
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
