<?php

namespace App\Filament\Company\Widgets;

use App\Models\SaleItem;
use Filament\Widgets\Widget;

class TopSellingProducts extends Widget
{
    protected string $view =
        'filament.company.widgets.top-selling-products';

    protected int|string|array $columnSpan = 1;

    public array $products = [];

    public function mount(): void
    {
        $companyId = auth()->user()->company_id;

        $this->products = SaleItem::query()
            ->whereHas('sale', function ($query) use ($companyId) {
                $query
                    ->where('company_id', $companyId)
                    ->where('status', 'completed')
                    ->whereDate('created_at', today());
            })
            ->with('product')
            ->selectRaw('
                product_id,
                SUM(quantity) as quantity_sold,
                SUM(line_total) as sales_total
            ')
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get()
            ->map(function ($item) {

                return [
                    'product_id' => $item->product_id,

                    'name' => $item->product?->product_name
                        ?? 'Unknown Product',

                    'quantity' => (float) $item->quantity_sold,

                    'sales' => (float) $item->sales_total,
                ];

            })
            ->values()
            ->toArray();
    }
}