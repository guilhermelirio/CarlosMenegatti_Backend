<?php

namespace App\Filament\Resources\DailyFees\Pages;

use App\Filament\Resources\DailyFees\DailyFeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyFees extends ListRecords
{
    protected static string $resource = DailyFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
