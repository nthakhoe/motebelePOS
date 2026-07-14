<?php

namespace App\Filament\Company\Resources\StockCounts;

use App\Filament\Company\Resources\StockCounts\Pages\CreateStockCount;
use App\Filament\Company\Resources\StockCounts\Pages\EditStockCount;
use App\Filament\Company\Resources\StockCounts\Pages\ListStockCounts;
use App\Filament\Company\Resources\StockCounts\Schemas\StockCountForm;
use App\Filament\Company\Resources\StockCounts\Tables\StockCountsTable;
use App\Models\StockCount;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockCountResource extends Resource
{
    protected static ?string $model = StockCount::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Count';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return StockCountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockCountsTable::configure($table);
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
            'index' => ListStockCounts::route('/'),
            'create' => CreateStockCount::route('/create'),
            'edit' => EditStockCount::route('/{record}/edit'),
        ];
    }
}
