<?php

namespace App\Filament\Company\Resources\PurchaseOrders\Pages;

use App\Filament\Company\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;
}
