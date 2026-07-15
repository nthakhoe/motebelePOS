<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\InventoryService;
use App\Services\PaymentService;

class SalesService
{

    public function __construct(
            protected InventoryService $inventoryService,

    protected PaymentService $paymentService,
    )
    {

    }

    protected function createSaleItems(
        Sale $sale,
        array $cart,
    ): void {

        foreach ($cart as $item) {

            $product = Product::with('unit')->findOrFail($item['id']);

            $unitPrice = (float) $item['price'];

            $quantity = (float) $item['quantity'];

            $discount = (float) ($item['discount'] ?? 0);

            $taxAmount = (float) ($item['tax'] ?? 0);

            $lineTotal = (float) $item['line_total'];

            $costPrice = (float) $product->cost_price;

            $profit = ($unitPrice - $costPrice) * $quantity;

            SaleItem::create([

                'sale_id' => $sale->id,

                'product_id' => $product->id,

                'unit_id' => $product->unit_id,

                'quantity' => $quantity,

                'unit_price' => $unitPrice,

                'discount' => $discount,

                'tax_rate' => $product->tax_rate ?? 0,

                'tax_amount' => $taxAmount,

                'line_total' => $lineTotal,

                'cost_price' => $costPrice,

                'profit' => $profit,

                'remarks' => null,

            ]);
        }
    }
    public function processSale(
        array $cart,
        int $customerId,
        int $paymentMethodId,
        float $amountReceived,
        User $cashier
    ): Sale {

        return DB::transaction(function () use (
            $cart,
            $customerId,
            $paymentMethodId,
            $amountReceived,
            $cashier
        ) {

            /*
             * Stage 1
             */
            $sale = $this->createSale(
                cart: $cart,
                customerId: $customerId,
                amountReceived: $amountReceived,
                cashier: $cashier,
            );

            /*
             * Stage 2
             */
            $this->createSaleItems(
                sale: $sale,
                cart: $cart,
            );

            /*
             * Stage 3
             */
            $this->updateInventory(
                sale: $sale,
                cart: $cart,
                cashier: $cashier,
            );

            /*
             * Stage 4
             */
            $this->paymentService->recordPayment(
                sale: $sale,
                paymentMethodId: 1,
                amountReceived: $sale['amount_paid'],
                referenceNumber: $sale['reference_number'] ?? null,
                authorizationCode: $sale['provider'] ?? null,
                cashier: auth()->user(),
            );

            return $sale;
        });
    }

    protected function createSale(
        array $cart,
        int $customerId,
        float $amountReceived,
        User $cashier,
    ): Sale {

        $subtotal = collect($cart)->sum('line_total');

        $discount = collect($cart)->sum('discount');

        $tax = collect($cart)->sum('tax');

        $total = $subtotal - $discount + $tax;

        return Sale::create([

            'company_id' => $cashier->company_id,

            'branch_id' => $cashier->branch_id,

            'register_id' => null,

            'user_id' => $cashier->id,

            'customer_id' => $customerId,

            'sale_number' => $this->generateSaleNumber(),

            'subtotal' => $subtotal,

            'discount' => $discount,

            'tax' => $tax,

            'total' => $total,

            'amount_paid' => $amountReceived,

            'change' => $amountReceived - $total,

            'sale_type' => 'Cash',

            'status' => 'Completed',

            'submitted_to_lekuka' => false,

            'remarks' => null,

            'completed_at' => now(),
        ]);
    }

    protected function generateSaleNumber(): string
    {
        return 'SAL-'.now()->format('YmdHis');
    }

    protected function updateInventory(
        Sale $sale,
        array $cart,
        User $cashier
    ): void {

        foreach ($cart as $item) {

            $product = Product::findOrFail($item['id']);

            $this->inventoryService->issueStock(

                product: $product,

                branchId: $cashier->branch_id,

                quantity: $item['quantity'],

                referenceType: Sale::class,

                referenceId: $sale->id,

                userId: $cashier->id

            );

        }

    }
}