<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationMemberships\Tables;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use App\Services\Organizations\OrganizationMembershipService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrganizationMembershipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Papel')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Vinculado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Papel')->options(OrganizationRole::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Remover acesso')
                    ->action(fn (OrganizationMembership $record, OrganizationMembershipService $service) => $service->remove($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
