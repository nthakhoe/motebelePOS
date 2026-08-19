<?php

namespace App\Filament\Company\Widgets;

use App\Models\Sale;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class SalesTrend extends Widget
{
    protected string $view = 'filament.company.widgets.sales-trend';

    protected int|string|array $columnSpan = 'full';

    public array $salesData = [];

    public float $totalSales = 0;

    public function mount(): void
    {
        $companyId = auth()->user()->company_id;

        $startDate = today()->subDays(6);
        $endDate = today();

        $sales = Sale::query()
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->endOfDay(),
            ])
            ->selectRaw('DATE(created_at) as sale_date')
            ->selectRaw('SUM(total) as total_sales')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        $this->salesData = [];

        for ($i = 0; $i < 7; $i++) {

            $date = $startDate->copy()->addDays($i);

            $dateKey = $date->format('Y-m-d');

            $amount = isset($sales[$dateKey])
                ? (float) $sales[$dateKey]->total_sales
                : 0;

            $this->salesData[] = [
                'date' => $dateKey,
                'label' => $date->format('D'),
                'short_date' => $date->format('d M'),
                'amount' => $amount,
            ];
        }

        $this->totalSales = collect($this->salesData)
            ->sum('amount');
    }
}