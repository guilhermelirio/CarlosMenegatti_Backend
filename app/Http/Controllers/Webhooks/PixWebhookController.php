<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Integrations\Pix\Exceptions\WebhookSignatureException;
use App\Integrations\Pix\PixManager;
use App\Jobs\ProcessPixWebhookJob;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Inbound Pix confirmation webhook. Auth is by signature/secret (verified inside the
 * provider's parser), so no Sanctum/CSRF. Responds fast and processes in a job.
 *
 * URL: POST /webhooks/pix/{provider}/{secret}
 */
class PixWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PixManager $manager): SymfonyResponse
    {
        try {
            $gateway = $manager->driver($provider);
            $event = $gateway->parseWebhook($request);
        } catch (WebhookSignatureException) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        $record = WebhookEvent::query()->firstOrCreate(
            ['provider' => $event->provider, 'external_id' => $event->eventId],
            ['payload' => $event->raw],
        );

        if ($record->wasRecentlyCreated) {
            ProcessPixWebhookJob::dispatch($record->id, $event->txid, $event->status->value)->afterCommit();
        }

        return response()->noContent(Response::HTTP_OK);
    }
}
