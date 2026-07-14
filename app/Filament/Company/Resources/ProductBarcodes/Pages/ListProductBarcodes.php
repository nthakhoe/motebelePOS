<?php

namespace App\Filament\Company\Resources\ProductBarcodes\Pages;

use App\Filament\Company\Resources\ProductBarcodes\ProductBarcodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductBarcodes extends ListRecords
{
    protected static string $resource = ProductBarcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
