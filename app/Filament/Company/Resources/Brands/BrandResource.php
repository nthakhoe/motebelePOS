<?php

namespace App\Filament\Company\Resources\Brands;

use App\Filament\Company\Resources\Brands\Pages\CreateBrand;
use App\Filament\Company\Resources\Brands\Pages\EditBrand;
use App\Filament\Company\Resources\Brands\Pages\ListBrands;
use App\Filament\Company\Resources\Brands\Schemas\BrandForm;
use App\Filament\Company\Resources\Brands\Tables\BrandsTable;
use App\Models\Brand;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Product Brands';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
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
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
