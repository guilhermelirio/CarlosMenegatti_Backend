<?php

namespace App\Filament\Resources\DailyFees\Pages;

use App\Filament\Resources\DailyFees\DailyFeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyFee extends EditRecord
{
    protected static string $resource = DailyFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
