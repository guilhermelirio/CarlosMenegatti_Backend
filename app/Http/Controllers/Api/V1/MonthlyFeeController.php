<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\Billing\MonthlyFeeData;
use App\Data\V1\Billing\PaymentData;
use App\Models\MonthlyFee;
use App\Services\Billing\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class MonthlyFeeController extends ApiController
{
    /**
     * List the authenticated athlete's monthly fees (newest first).
     *
     * @return DataCollection<int, MonthlyFeeData>
     */
    public function index(Request $request): DataCollection
    {
        $fees = $this->currentPlayer($request)
            ->monthlyFees()
            ->orderByDesc('reference_year')
            ->orderByDesc('reference_month')
            ->get();

        return MonthlyFeeData::collect($fees, DataCollection::class);
    }

    /** Start a Pix charge for one of the athlete's monthly fees. */
    public function pix(Request $request, MonthlyFee $monthlyFee, PaymentService $service): JsonResponse
    {
        $this->authorize('pay', $monthlyFee);

        $payment = $service->initiatePix($monthlyFee);

        return response()->json(PaymentData::fromModel($payment), 201);
    }
}
