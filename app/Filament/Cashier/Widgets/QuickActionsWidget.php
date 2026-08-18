<?php

namespace App\Filament\Cashier\Widgets;

use App\Filament\Cashier\Pages\POS;
use App\Filament\Cashier\Pages\ShiftManagement;
use App\Filament\Cashier\Resources\Sales\SaleResource;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected string $view =
        'filament.cashier.widgets.quick-actions-widget';

    protected int|string|array $columnSpan = 2;

    public function posUrl(): string
    {
        return POS::getUrl();
    }

    public function salesUrl(): string
    {
        return SaleResource::getUrl('index');
    }

    public function shiftUrl(): string
    {
        return ShiftManagement::getUrl();
    }
}