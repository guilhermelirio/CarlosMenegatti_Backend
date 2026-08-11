<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\Billing\DailyFeeData;
use App\Data\V1\Billing\PaymentData;
use App\Models\DailyFee;
use App\Services\Billing\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class DailyFeeController extends ApiController
{
    /**
     * List the authenticated athlete's daily fees (newest first).
     *
     * @return DataCollection<int, DailyFeeData>
     */
    public function index(Request $request): DataCollection
    {
        $fees = $this->currentPlayer($request)
            ->dailyFees()
            ->with('gameSession')
            ->latest()
            ->get();

        return DailyFeeData::collect($fees, DataCollection::class);
    }

    /** Start a Pix charge for one of the athlete's daily fees. */
    public function pix(Request $request, DailyFee $dailyFee, PaymentService $service): JsonResponse
    {
        $this->authorize('pay', $dailyFee);

        $payment = $service->initiatePix($dailyFee);

        return response()->json(PaymentData::fromModel($payment), 201);
    }
}
