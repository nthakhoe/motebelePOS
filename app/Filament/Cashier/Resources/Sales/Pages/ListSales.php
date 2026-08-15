<?php

namespace App\Filament\Cashier\Resources\Sales\Pages;

use App\Filament\Cashier\Resources\Sales\SaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Cashier\Resources\Sales\Pages\ListSales;
use App\Filament\Cashier\Resources\Sales\Pages\ViewSale;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
