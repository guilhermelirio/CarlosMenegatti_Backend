<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\RelationManagers;

use App\Models\Attendance;
use App\Models\Player;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Presenças';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('player_id')
                ->label('Atleta')
                ->options(fn () => Player::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Toggle::make('confirmed')->label('Confirmado')->default(true),
            Toggle::make('attended')->label('Compareceu')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('player_id')
            ->columns([
                TextColumn::make('player.name')
                    ->label('Atleta')
                    ->description(fn (Attendance $r) => $r->player?->membership_type?->label())
                    ->searchable(),
                IconColumn::make('confirmed')->label('Confirmado')->boolean(),
                IconColumn::make('attended')->label('Compareceu')->boolean(),
                TextColumn::make('dailyFee.status')
                    ->label('Diária gerada')
                    ->badge()
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Registrar presença'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
