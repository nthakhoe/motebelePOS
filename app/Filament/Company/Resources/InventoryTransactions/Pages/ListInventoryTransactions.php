<?php

namespace App\Filament\Company\Resources\InventoryTransactions\Pages;

use App\Filament\Company\Resources\InventoryTransactions\InventoryTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactions extends ListRecords
{
    protected static string $resource = InventoryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
