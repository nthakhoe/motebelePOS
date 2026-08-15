<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\Lekuka\receiptService;

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

            $tax = app(\App\Services\TaxService::class)
                ->calculateInclusive(
                    price: $unitPrice,
                    qty: $quantity,
                    rate: (float) ($product->tax_rate ?? 0),
                );

            $taxAmount = $tax['tax'];


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
            |--------------------------------------------------------------------------
            | Stage 1: Create Sale
            |--------------------------------------------------------------------------
            */

            $sale = $this->createSale(
                cart: $cart,
                customerId: $customerId,
                amountReceived: $amountReceived,
                cashier: $cashier,
            );

            /*
            |--------------------------------------------------------------------------
            | Stage 2: Create Sale Items
            |--------------------------------------------------------------------------
            */

            $this->createSaleItems(
                sale: $sale,
                cart: $cart,
            );

            /*
            |--------------------------------------------------------------------------
            | Stage 3: Update Inventory
            |--------------------------------------------------------------------------
            */

            $this->updateInventory(
                sale: $sale,
                cart: $cart,
                cashier: $cashier,
            );

            /*
            |--------------------------------------------------------------------------
            | Stage 4: Record Payment
            |--------------------------------------------------------------------------
            */

            $this->paymentService->recordPayment(

                sale: $sale,

                paymentMethodId: $paymentMethodId,

                amountReceived: $amountReceived,

                referenceNumber: $sale->reference_number ?? null,

                authorizationCode: $sale->provider ?? null,

                cashier: $cashier,

            );

            /*
            |--------------------------------------------------------------------------
            | Stage 5: Record Session
            |--------------------------------------------------------------------------
            */

            app(RegisterSessionService::class)->recordSale($sale);

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Sale With Relationships
            |--------------------------------------------------------------------------
            */

            return $sale->fresh([
                'customer',
                'items.product',
                'payments.paymentMethod',
            ]);

        });
    }

    public function getRegisteredDeviceForBranch(int $branchId): LekukaDevice
    {
        return LekukaDevice::query()
            ->where('branch_id', $branchId)
            ->where('registered', true)
            ->firstOrFail();
    }

    protected function createSale(
        array $cart,
        int $customerId,
        float $amountReceived,
        User $cashier,
    ): Sale {

        $subtotal = 0.00;
        $vat = 0.00;
        $discount = 0.00;
        $total = 0.00;

        $taxService = app(\App\Services\TaxService::class);

        foreach ($cart as $item) {

            $result = $taxService->calculateInclusive(
                price: (float) $item['price'],
                qty: (float) $item['quantity'],
                rate: (float) $item['tax'],
            );

            $subtotal += $result['subtotal'];

            $vat += $result['tax'];

            $total += $result['total'];

            $discount += (float) ($item['discount'] ?? 0);
        }

        $subtotal = round($subtotal, 2);
        $vat = round($vat, 2);
        $discount = round($discount, 2);

        $total = round($total - $discount, 2);

        return Sale::create([

            'company_id' => $cashier->company_id,

            'branch_id' => $cashier->branch_id,

            'register_id' => null,

            'user_id' => $cashier->id,

            'customer_id' => $customerId,

            'sale_number' => $this->generateSaleNumber(),

            'subtotal' => $subtotal,

            'discount' => $discount,

            'tax' => $vat,

            'total' => $total,

            'amount_paid' => $amountReceived,

            'change' => round($amountReceived - $total, 2),

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