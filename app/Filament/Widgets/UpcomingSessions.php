<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\GameSession;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingSessions extends TableWidget
{
    protected static ?string $heading = 'Próximas sessões';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GameSession::query()
                    ->whereDate('scheduled_date', '>=', CarbonImmutable::now()->toDateString())
                    ->withCount(['attendances as confirmed_count' => fn (Builder $q) => $q->where('confirmed', true)])
                    ->orderBy('scheduled_date')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('scheduled_date')->label('Data')->date('d/m/Y'),
                TextColumn::make('start_time')->label('Horário'),
                TextColumn::make('location')->label('Local')->placeholder('—'),
                TextColumn::make('daily_fee_cents')->label('Diária')->money('BRL', divideBy: 100),
                TextColumn::make('confirmed_count')->label('Confirmados')->badge()->color('info'),
            ])
            ->paginated(false);
    }
}
