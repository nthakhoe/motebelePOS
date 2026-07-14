<?php

namespace App\Filament\Company\Resources\StockCounts\Pages;

use App\Filament\Company\Resources\StockCounts\StockCountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockCount extends CreateRecord
{
    protected static string $resource = StockCountResource::class;
}
