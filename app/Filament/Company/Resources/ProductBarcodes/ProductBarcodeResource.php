<?php

namespace App\Filament\Company\Resources\ProductBarcodes;

use App\Filament\Company\Resources\ProductBarcodes\Pages\CreateProductBarcode;
use App\Filament\Company\Resources\ProductBarcodes\Pages\EditProductBarcode;
use App\Filament\Company\Resources\ProductBarcodes\Pages\ListProductBarcodes;
use App\Filament\Company\Resources\ProductBarcodes\Schemas\ProductBarcodeForm;
use App\Filament\Company\Resources\ProductBarcodes\Tables\ProductBarcodesTable;
use App\Models\ProductBarcode;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductBarcodeResource extends Resource
{
    protected static ?string $model = ProductBarcode::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Product Barcode';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return ProductBarcodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductBarcodesTable::configure($table);
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
            'index' => ListProductBarcodes::route('/'),
            'create' => CreateProductBarcode::route('/create'),
            'edit' => EditProductBarcode::route('/{record}/edit'),
        ];
    }
}
