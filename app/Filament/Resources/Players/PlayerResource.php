<?php

namespace App\Filament\Resources\Players;

use App\Filament\Concerns\AuthorizesOrganizationOperations;
use App\Filament\Resources\Players\Pages\CreatePlayer;
use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Filament\Resources\Players\Schemas\PlayerForm;
use App\Filament\Resources\Players\Tables\PlayersTable;
use App\Models\Player;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlayerResource extends Resource
{
    use AuthorizesOrganizationOperations;

    protected static function treasurerCanWrite(): bool
    {
        return true;
    }

    protected static ?string $model = Player::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Atletas';

    protected static ?string $navigationLabel = 'Atletas';

    protected static ?string $modelLabel = 'Atleta';

    protected static ?string $pluralModelLabel = 'Atletas';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PlayerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlayersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlayers::route('/'),
            'create' => CreatePlayer::route('/create'),
            'edit' => EditPlayer::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
