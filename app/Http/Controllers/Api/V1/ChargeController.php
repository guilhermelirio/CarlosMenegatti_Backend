<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\Billing\ChargeData;
use App\Data\V1\Billing\PaymentData;
use App\Enums\FeeStatus;
use App\Models\Charge;
use App\Models\DailyFee;
use App\Models\MonthlyFee;
use App\Services\Billing\PaymentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\DataCollection;

class ChargeController extends ApiController
{
    /**
     * List all charges belonging to the authenticated athlete.
     *
     * @return DataCollection<int, ChargeData>
     */
    public function index(Request $request): DataCollection
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['monthly', 'daily'])],
            'status' => ['nullable', Rule::enum(FeeStatus::class)],
        ]);

        $charges = Charge::query()
            ->where('player_id', $this->currentPlayer($request)->id)
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('charge_type', $type))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('reference_date')
            ->orderByDesc('created_at')
            ->get();

        return $this->toDataCollection($charges);
    }

    public function show(Request $request, Charge $charge): ChargeData
    {
        $this->ensureOwnedByCurrentPlayer($request, $charge);

        return $this->toData($charge);
    }

    public function pix(
        Request $request,
        Charge $charge,
        PaymentService $service,
    ): JsonResponse {
        $this->ensureOwnedByCurrentPlayer($request, $charge);
        $payable = $charge->underlying();
        abort_if($payable === null, 404);
        $this->authorize('pay', $payable);

        return response()->json(PaymentData::fromModel($service->initiatePix($payable)), 201);
    }

    /** @param Collection<int, Charge> $charges */
    private function toDataCollection(Collection $charges): DataCollection
    {
        $monthlyIds = $charges->where('charge_type', 'monthly')->pluck('id');
        $dailyIds = $charges->where('charge_type', 'daily')->pluck('id');
        $monthlyFees = MonthlyFee::query()->whereKey($monthlyIds)->get()->keyBy('id');
        $dailyFees = DailyFee::query()->with('gameSession')->whereKey($dailyIds)->get()->keyBy('id');

        $data = $charges->map(function (Charge $charge) use ($monthlyFees, $dailyFees): ChargeData {
            $underlying = $charge->charge_type === 'monthly'
                ? $monthlyFees->get($charge->id)
                : $dailyFees->get($charge->id);

            abort_if(! $underlying instanceof MonthlyFee && ! $underlying instanceof DailyFee, 404);

            return ChargeData::fromModels($charge, $underlying);
        });

        return ChargeData::collect($data, DataCollection::class);
    }

    private function toData(Charge $charge): ChargeData
    {
        $underlying = $charge->underlying();
        abort_if($underlying === null, 404);

        if ($underlying instanceof DailyFee) {
            $underlying->load('gameSession');
        }

        return ChargeData::fromModels($charge, $underlying);
    }

    private function ensureOwnedByCurrentPlayer(Request $request, Charge $charge): void
    {
        abort_unless($charge->player_id === $this->currentPlayer($request)->id, 404);
    }
}
