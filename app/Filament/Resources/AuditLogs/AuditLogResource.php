<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Concerns\AuthorizesOrganizationOperations;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    use AuthorizesOrganizationOperations;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Auditoria';

    protected static ?string $modelLabel = 'Registro de auditoria';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Responsável')->placeholder('Sistema')->searchable(),
                TextColumn::make('event')->label('Evento')->badge()->searchable(),
                TextColumn::make('subject_type')->label('Registro')->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('subject_id')->label('Identificador')->limit(12)->copyable(),
                TextColumn::make('reason')->label('Motivo')->limit(50)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('event')->label('Evento')->options(fn (): array => AuditLog::query()->distinct()->pluck('event', 'event')->all()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
