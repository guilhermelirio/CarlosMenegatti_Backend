<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges;

use App\Filament\Concerns\AuthorizesOrganizationOperations;
use App\Filament\Resources\Charges\Pages\ListCharges;
use App\Filament\Resources\Charges\Tables\ChargesTable;
use App\Models\Charge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lista unificada de cobranças (mensalidades + diárias). Read-only, sobre a
 * view `charges`. Baixa manual feita pela ação "Receber" na linha.
 */
class ChargeResource extends Resource
{
    use AuthorizesOrganizationOperations;

    protected static function treasurerCanWrite(): bool
    {
        return true;
    }

    protected static ?string $model = Charge::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Cobranças';

    protected static ?string $modelLabel = 'Cobrança';

    protected static ?string $pluralModelLabel = 'Cobranças';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return ChargesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('player');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharges::route('/'),
        ];
    }
}
