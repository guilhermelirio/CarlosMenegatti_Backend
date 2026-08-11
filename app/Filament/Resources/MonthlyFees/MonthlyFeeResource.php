<?php

declare(strict_types=1);

namespace App\Filament\Resources\MonthlyFees;

use App\Filament\Concerns\AuthorizesOrganizationOperations;
use App\Filament\Resources\MonthlyFees\Pages\CreateMonthlyFee;
use App\Filament\Resources\MonthlyFees\Pages\EditMonthlyFee;
use App\Filament\Resources\MonthlyFees\Pages\ListMonthlyFees;
use App\Filament\Resources\MonthlyFees\Schemas\MonthlyFeeForm;
use App\Filament\Resources\MonthlyFees\Tables\MonthlyFeesTable;
use App\Models\MonthlyFee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MonthlyFeeResource extends Resource
{
    use AuthorizesOrganizationOperations;

    protected static function treasurerCanWrite(): bool
    {
        return true;
    }

    protected static ?string $model = MonthlyFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Mensalidades';

    protected static ?string $modelLabel = 'Mensalidade';

    protected static ?string $pluralModelLabel = 'Mensalidades';

    protected static ?int $navigationSort = 10;

    // Substituída no menu pela lista unificada "Cobranças" (ChargeResource).
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return MonthlyFeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonthlyFeesTable::configure($table);
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
            'index' => ListMonthlyFees::route('/'),
            'create' => CreateMonthlyFee::route('/create'),
            'edit' => EditMonthlyFee::route('/{record}/edit'),
        ];
    }
}
