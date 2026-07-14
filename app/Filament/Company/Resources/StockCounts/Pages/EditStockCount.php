<?php

namespace App\Filament\Company\Resources\StockCounts\Pages;

use App\Filament\Company\Resources\StockCounts\StockCountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockCount extends EditRecord
{
    protected static string $resource = StockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
