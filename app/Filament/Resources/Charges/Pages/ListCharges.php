<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges\Pages;

use App\Filament\Resources\Charges\ChargeResource;
use App\Services\Billing\FeeGenerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCharges extends ListRecords
{
    protected static string $resource = ChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateMonth')
                ->label('Gerar mensalidades do mês')
                ->icon('heroicon-o-sparkles')
                ->schema([
                    Select::make('month')
                        ->label('Mês')
                        ->options(array_combine(range(1, 12), range(1, 12)))
                        ->default((int) date('n'))
                        ->required(),
                    Select::make('year')
                        ->label('Ano')
                        ->options(array_combine(range(2024, 2030), range(2024, 2030)))
                        ->default((int) date('Y'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $created = app(FeeGenerationService::class)
                        ->generateForMonth((int) $data['year'], (int) $data['month']);

                    Notification::make()
                        ->title("{$created} mensalidade(s) gerada(s).")
                        ->success()
                        ->send();
                }),
        ];
    }
}
