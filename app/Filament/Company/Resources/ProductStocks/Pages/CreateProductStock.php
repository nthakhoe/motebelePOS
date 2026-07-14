<?php

namespace App\Filament\Company\Resources\ProductStocks\Pages;

use App\Filament\Company\Resources\ProductStocks\ProductStockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductStock extends CreateRecord
{
    protected static string $resource = ProductStockResource::class;
}
