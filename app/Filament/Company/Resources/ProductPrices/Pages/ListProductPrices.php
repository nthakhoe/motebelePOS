<?php

namespace App\Filament\Company\Resources\ProductPrices\Pages;

use App\Filament\Company\Resources\ProductPrices\ProductPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductPrices extends ListRecords
{
    protected static string $resource = ProductPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
