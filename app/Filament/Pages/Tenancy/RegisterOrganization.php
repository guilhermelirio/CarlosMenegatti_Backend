<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Models\User;
use App\Services\Organizations\OrganizationOnboardingService;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class RegisterOrganization extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Criar organização';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(120)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')
                ->label('Identificador da URL')
                ->helperText('Usado no endereço do painel. Exemplo: meu-grupo.')
                ->required()
                ->alphaDash()
                ->maxLength(120)
                ->unique(),
        ]);
    }

    /** @param array<string, mixed> $data */
    protected function handleRegistration(array $data): Model
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw new RuntimeException('Usuário autenticado não encontrado.');
        }

        return app(OrganizationOnboardingService::class)->create(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            administrator: $user,
        );
    }
}
