<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationMemberships;

use App\Filament\Resources\OrganizationMemberships\Pages\CreateOrganizationMembership;
use App\Filament\Resources\OrganizationMemberships\Pages\EditOrganizationMembership;
use App\Filament\Resources\OrganizationMemberships\Pages\ListOrganizationMemberships;
use App\Filament\Resources\OrganizationMemberships\Schemas\OrganizationMembershipForm;
use App\Filament\Resources\OrganizationMemberships\Tables\OrganizationMembershipsTable;
use App\Models\OrganizationMembership;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrganizationMembershipResource extends Resource
{
    protected static ?string $model = OrganizationMembership::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Usuários e acessos';

    protected static ?string $modelLabel = 'Acesso';

    protected static ?string $pluralModelLabel = 'Usuários e acessos';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return OrganizationMembershipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationMembershipsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationMemberships::route('/'),
            'create' => CreateOrganizationMembership::route('/create'),
            'edit' => EditOrganizationMembership::route('/{record}/edit'),
        ];
    }
}
