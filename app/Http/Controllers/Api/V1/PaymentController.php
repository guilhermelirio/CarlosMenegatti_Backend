<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\Billing\PaymentData;
use App\Models\Payment;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

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
}
