<?php

namespace App\Filament\Company\Resources\InventoryTransactions\Pages;

use App\Filament\Company\Resources\InventoryTransactions\InventoryTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryTransaction extends CreateRecord
{
    protected static string $resource = InventoryTransactionResource::class;
}
