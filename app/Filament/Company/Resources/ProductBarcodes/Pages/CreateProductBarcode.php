<?php

namespace App\Filament\Company\Resources\ProductBarcodes\Pages;

use App\Filament\Company\Resources\ProductBarcodes\ProductBarcodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductBarcode extends CreateRecord
{
    protected static string $resource = ProductBarcodeResource::class;
}
