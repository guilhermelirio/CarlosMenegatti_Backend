<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditOrganizationProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Configurações da organização';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(120),
            TextInput::make('slug')
                ->label('Identificador da URL')
                ->helperText('Alterar este valor também altera o endereço do painel.')
                ->required()
                ->alphaDash()
                ->maxLength(120)
                ->unique(ignoreRecord: true),
        ]);
    }

    protected function getRedirectUrl(): ?string
    {
        return Filament::getUrl($this->tenant);
    }
}
