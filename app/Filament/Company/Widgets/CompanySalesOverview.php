<?php

namespace App\Filament\Company\Widgets;

use App\Models\Sale;
use Filament\Widgets\Widget;

class CompanySalesOverview extends Widget
{
    protected string $view = 'filament.company.widgets.company-sales-overview';

    protected int|string|array $columnSpan = 'full';

    public float $todaySales = 0;

    public float $todayNetSales = 0;

    public float $todayRefunds = 0;

    public int $todayTransactions = 0;

    public function mount(): void
    {
        $companyId = auth()->user()->company_id;

        $todaySalesQuery = Sale::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', today());

        $this->todaySales = (float) (clone $todaySalesQuery)
            ->where('status', 'completed')
            ->sum('total');

        $this->todayRefunds = (float) (clone $todaySalesQuery)
            ->where('status', 'refunded')
            ->sum('total');

        $this->todayNetSales =
            $this->todaySales - $this->todayRefunds;

        $this->todayTransactions = (clone $todaySalesQuery)
            ->where('status', 'completed')
            ->count();
    }
}