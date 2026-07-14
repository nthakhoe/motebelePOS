<?php

namespace App\Filament\Company\Resources\ProductPrices;

use App\Filament\Company\Resources\ProductPrices\Pages\CreateProductPrice;
use App\Filament\Company\Resources\ProductPrices\Pages\EditProductPrice;
use App\Filament\Company\Resources\ProductPrices\Pages\ListProductPrices;
use App\Filament\Company\Resources\ProductPrices\Schemas\ProductPriceForm;
use App\Filament\Company\Resources\ProductPrices\Tables\ProductPricesTable;
use App\Models\ProductPrice;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductPriceResource extends Resource
{
    protected static ?string $model = ProductPrice::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Product Prices';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return ProductPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductPricesTable::configure($table);
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
            'index' => ListProductPrices::route('/'),
            'create' => CreateProductPrice::route('/create'),
            'edit' => EditProductPrice::route('/{record}/edit'),
        ];
    }
}
