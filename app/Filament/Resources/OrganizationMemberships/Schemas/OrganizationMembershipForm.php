<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationMemberships\Schemas;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizationMembershipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(120)
                ->visibleOn('create'),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->maxLength(255)
                ->visibleOn('create'),
            TextInput::make('password')
                ->label('Senha inicial')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->visibleOn('create'),
            TextInput::make('member_name')
                ->label('Nome')
                ->formatStateUsing(fn (?string $state, ?OrganizationMembership $record): ?string => $record?->user?->name)
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit'),
            TextInput::make('member_email')
                ->label('E-mail')
                ->formatStateUsing(fn (?string $state, ?OrganizationMembership $record): ?string => $record?->user?->email)
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit'),
            Select::make('role')
                ->label('Papel')
                ->options(OrganizationRole::class)
                ->default(OrganizationRole::Member->value)
                ->required(),
        ]);
    }
}
