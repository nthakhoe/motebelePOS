<?php

namespace App\Filament\Company\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id;

        $totalProducts = Product::where('company_id', $companyId)->count();

        $activeProducts = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $serviceProducts = Product::where('company_id', $companyId)
            ->where('is_service', true)
            ->count();

        $inactiveProducts = Product::where('company_id', $companyId)
            ->where('is_active', false)
            ->count();

        return [

            Stat::make('Total Products', number_format($totalProducts))
                ->description('Products in catalogue')
                ->descriptionIcon('heroicon-m-cube')
                ->icon('heroicon-o-cube'),

            Stat::make('Active Products', number_format($activeProducts))
                ->description('Available for sale')
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Service Products', number_format($serviceProducts))
                ->description('Non-stock items')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->icon('heroicon-o-wrench-screwdriver'),

            Stat::make('Inactive Products', number_format($inactiveProducts))
                ->description('Disabled products')
                ->descriptionIcon('heroicon-m-x-circle')
                ->icon('heroicon-o-x-circle'),

        ];
    }
}