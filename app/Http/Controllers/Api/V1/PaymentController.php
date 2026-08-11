<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\Billing\PaymentData;
use App\Models\Payment;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends ApiController
{
    /**
     * Payment history for the authenticated athlete.
     *
     * @return DataCollection<int, PaymentData>
     */
    public function index(Request $request): DataCollection
    {
        $payments = $this->currentPlayer($request)
            ->payments()
            ->with('payable')
            ->latest()
            ->get();

        return PaymentData::collect($payments, DataCollection::class);
    }

    /** Current status of a single payment (used to poll a Pix charge). */
    public function show(Request $request, Payment $payment): PaymentData
    {
        $this->authorize('view', $payment);

        return PaymentData::fromModel($payment->load('payable'));
    }

    public function uploadReceipt(Request $request, Payment $payment, AuditService $audit): JsonResponse
    {
        $this->authorize('view', $payment);
        $validated = $request->validate([
            'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $file = $validated['receipt'];
        $path = $file->store("payment-receipts/{$payment->organization_id}", 'local');

        abort_if($path === false, 500, 'Não foi possível guardar o comprovante.');

        $before = $payment->attributesToArray();
        $payment->update([
            'receipt_path' => $path,
            'receipt_uploaded_at' => now(),
        ]);
        $audit->record('payment_receipt_uploaded', $payment, $before, $payment->fresh()->attributesToArray());

        return response()->json(PaymentData::fromModel($payment->refresh()));
    }

    public function downloadReceipt(Request $request, Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);
        abort_if($payment->receipt_path === null || ! Storage::disk('local')->exists($payment->receipt_path), 404);

        return Storage::disk('local')->download($payment->receipt_path);
    }
}
