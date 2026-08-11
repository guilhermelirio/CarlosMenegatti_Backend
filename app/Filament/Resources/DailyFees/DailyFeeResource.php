<?php

declare(strict_types=1);

namespace App\Filament\Resources\DailyFees;

use App\Filament\Concerns\AuthorizesOrganizationOperations;
use App\Filament\Resources\DailyFees\Pages\CreateDailyFee;
use App\Filament\Resources\DailyFees\Pages\EditDailyFee;
use App\Filament\Resources\DailyFees\Pages\ListDailyFees;
use App\Filament\Resources\DailyFees\Schemas\DailyFeeForm;
use App\Filament\Resources\DailyFees\Tables\DailyFeesTable;
use App\Models\DailyFee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DailyFeeResource extends Resource
{
    use AuthorizesOrganizationOperations;

    protected static function treasurerCanWrite(): bool
    {
        return true;
    }

    protected static ?string $model = DailyFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Diárias';

    protected static ?string $modelLabel = 'Diária';

    protected static ?string $pluralModelLabel = 'Diárias';

    protected static ?int $navigationSort = 11;

    // Substituída no menu pela lista unificada "Cobranças" (ChargeResource).
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return DailyFeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyFeesTable::configure($table);
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
            'index' => ListDailyFees::route('/'),
            'create' => CreateDailyFee::route('/create'),
            'edit' => EditDailyFee::route('/{record}/edit'),
        ];
    }
}
