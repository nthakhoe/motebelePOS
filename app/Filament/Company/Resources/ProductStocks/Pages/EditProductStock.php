<?php

namespace App\Filament\Company\Resources\ProductStocks\Pages;

use App\Filament\Company\Resources\ProductStocks\ProductStockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductStock extends EditRecord
{
    protected static string $resource = ProductStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
