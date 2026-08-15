<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\View\View;

class SaleReceiptController extends Controller
{
    public function __invoke(Sale $sale): View
    {
        $sale->load([
            'company',
            'branch',
            'cashier',
            'customer',
            'register',
            'items.product',
            'items.unit',
            'payments.paymentMethod',
        ]);

        return view('cashier.sales.receipt', [
            'sale' => $sale,
        ]);
    }
}