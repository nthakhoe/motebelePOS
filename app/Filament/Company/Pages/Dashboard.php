<?php

namespace App\Filament\Company\Pages;

use App\Filament\Company\Widgets\CompanySalesOverview;
use App\Filament\Company\Widgets\PaymentMethodBreakdown;
use App\Filament\Company\Widgets\TopSellingProducts;
use App\Filament\Company\Widgets\SalesTrend;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            CompanySalesOverview::class,
            SalesTrend::class,
            PaymentMethodBreakdown::class,
            TopSellingProducts::class,
        ];
    }
}