<?php

namespace App\Filament\Company\Resources\ProductBarcodes\Pages;

use App\Filament\Company\Resources\ProductBarcodes\ProductBarcodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductBarcode extends EditRecord
{
    protected static string $resource = ProductBarcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
