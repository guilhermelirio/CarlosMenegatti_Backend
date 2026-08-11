<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Integrations\Pix\Exceptions\WebhookSignatureException;
use App\Integrations\Pix\PixManager;
use App\Jobs\ProcessPixWebhookJob;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Tenancy\CurrentOrganization;
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
    public function __invoke(
        Request $request,
        string $provider,
        PixManager $manager,
        CurrentOrganization $currentOrganization,
    ): SymfonyResponse {
        try {
            $gateway = $manager->driver($provider);
            $event = $gateway->parseWebhook($request);
        } catch (WebhookSignatureException) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        $payment = Payment::withoutGlobalScopes()->where('pix_txid', $event->txid)->first();

        if ($payment === null) {
            return response()->noContent(Response::HTTP_OK);
        }

        $organization = $payment->organization()->firstOrFail();
        $currentOrganization->set($organization);

        $record = WebhookEvent::query()->firstOrCreate(
            ['provider' => $event->provider, 'external_id' => $event->eventId],
            ['payload' => $event->raw],
        );

        if ($record->wasRecentlyCreated) {
            ProcessPixWebhookJob::dispatch(
                $organization->id,
                $record->id,
                $event->txid,
                $event->status->value,
            )->afterCommit();
        }

        $currentOrganization->clear();

        return response()->noContent(Response::HTTP_OK);
    }
}
