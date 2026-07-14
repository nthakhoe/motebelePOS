<?php

namespace App\Filament\Company\Resources\ProductPrices\Pages;

use App\Filament\Company\Resources\ProductPrices\ProductPriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductPrice extends CreateRecord
{
    protected static string $resource = ProductPriceResource::class;
}
