<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Active Pix provider
    |--------------------------------------------------------------------------
    | "static" = Pix MANUAL: gera o BR Code estático localmente com a chave Pix
    | da pelada; a confirmação é feita manualmente pelo tesoureiro no painel.
    | "fake" = fluxo simulado com webhook (usado em testes). Provedores reais
    | (Efí, Mercado Pago, Asaas, Woovi, ...) plugam aqui no futuro.
    */
    'default' => env('PIX_PROVIDER', 'static'),

    'expires_in_seconds' => (int) env('PIX_EXPIRES_IN_SECONDS', 3600),

    /*
    | Pix manual (BR Code estático). Estes valores são apenas FALLBACKS — os
    | reais são editáveis no painel (Configuração de valores) e guardados em
    | settings, que têm prioridade sobre este bloco.
    */
    'static' => [
        'key' => env('PIX_KEY'),
        'key_type' => env('PIX_KEY_TYPE', 'email'),
        'receiver_name' => env('PIX_RECEIVER_NAME', 'PELADA C MENEGATTI'),
        'city' => env('PIX_CITY', 'SAO PAULO'),
    ],

    'fake' => [
        // Secret embedded in the simulated webhook URL: /webhooks/pix/fake/{secret}
        'webhook_secret' => env('PIX_FAKE_WEBHOOK_SECRET', 'fake-secret'),
    ],

    // Placeholder blocks for future real providers (BYOK: credentials per install).
    'efi' => [
        'client_id' => env('PIX_EFI_CLIENT_ID'),
        'client_secret' => env('PIX_EFI_CLIENT_SECRET'),
        'pix_key' => env('PIX_EFI_KEY'),
        'webhook_secret' => env('PIX_EFI_WEBHOOK_SECRET'),
        'sandbox' => (bool) env('PIX_EFI_SANDBOX', true),
    ],
];
