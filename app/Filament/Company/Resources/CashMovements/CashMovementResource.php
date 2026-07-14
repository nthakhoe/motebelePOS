<?php

namespace App\Filament\Company\Resources\CashMovements;

use App\Filament\Company\Resources\CashMovements\Pages\CreateCashMovement;
use App\Filament\Company\Resources\CashMovements\Pages\EditCashMovement;
use App\Filament\Company\Resources\CashMovements\Pages\ListCashMovements;
use App\Filament\Company\Resources\CashMovements\Schemas\CashMovementForm;
use App\Filament\Company\Resources\CashMovements\Tables\CashMovementsTable;
use App\Models\CashMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashMovementResource extends Resource
{
    protected static ?string $model = CashMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CashMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashMovementsTable::configure($table);
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
            'index' => ListCashMovements::route('/'),
            'create' => CreateCashMovement::route('/create'),
            'edit' => EditCashMovement::route('/{record}/edit'),
        ];
    }
}
